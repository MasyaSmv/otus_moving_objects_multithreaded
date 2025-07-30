<?php

namespace Masyasmv\Multithreaded;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use parallel\Channel;
use parallel\Runtime;

class Manager
{
    private string $cmdChannelName;
    private Channel $cmdChannel;

    private string $ackChannelName;
    private Channel $ackChannel;

    /** @var Runtime[] */
    private array $runtimes = [];
    /** @var \parallel\Future[] */
    private array $futures = [];

    /**
     * @var int Number of pending commands enqueued
     */
    private int $pending = 0;

    public function __construct(int $threads = 4)
    {
        // Create command and ack channels
        $this->cmdChannelName = uniqid('cmd_', true);
        $this->cmdChannel = Channel::make($this->cmdChannelName, Channel::Infinite);

        $this->ackChannelName = uniqid('ack_', true);
        $this->ackChannel = Channel::make($this->ackChannelName, Channel::Infinite);

        // Spawn runtimes
        for ($i = 0; $i < $threads; $i++) {
            $rt = new Runtime();
            $future = $rt->run(function(string $cmdName, string $ackName) {
                $ch = Channel::open($cmdName);
                $ackCh = Channel::open($ackName);

                while (true) {
                    try {
                        $cmd = $ch->recv();
                    } catch (\parallel\Channel\Error $e) {
                        break; // channel closed
                    }
                    if ($cmd === null) {
                        break; // poison pill
                    }
                    // execute command
                    try {
                        $cmd->execute();
                    } catch (\Throwable $e) {
                        // ignore errors
                    }
                    // acknowledge completion
                    try {
                        $ackCh->send(true);
                    } catch (\parallel\Channel\Error $e) {
                        // ignore
                    }
                }
            }, [$this->cmdChannelName, $this->ackChannelName]);

            $this->runtimes[] = $rt;
            $this->futures[] = $future;
        }
    }

    public function enqueue(CommandInterface $cmd): void
    {
        $this->pending++;
        $this->cmdChannel->send($cmd);
    }

    public function softStop(): void
    {
        // send poison pills
        foreach ($this->runtimes as $_) {
            $this->cmdChannel->send(null);
        }
        // wait acknowledgments for all pending commands
        for ($i = 0; $i < $this->pending; $i++) {
            $this->ackChannel->recv();
        }
        // close channels (unblocks threads if waiting)
        $this->cmdChannel->close();
        $this->ackChannel->close();
        // ensure futures complete
        foreach ($this->futures as $future) {
            $future->value();
        }
        // clear state
        $this->runtimes = [];
        $this->futures = [];
        $this->pending = 0;
    }

    public function hardStop(): void
    {
        // close cmd channel (unblocks recv)
        $this->cmdChannel->close();
        $this->ackChannel->close();
        // wait all futures
        foreach ($this->futures as $future) {
            try {
                $future->value();
            } catch (\Throwable $e) {
                // ignore
            }
        }
        // clear state
        $this->runtimes = [];
        $this->futures = [];
        $this->pending = 0;
    }

    /**
     * @return Runtime[]
     */
    public function getRuntimes(): array
    {
        return $this->runtimes;
    }

    public function __destruct()
    {
        if (!empty($this->runtimes)) {
            $this->hardStop();
        }
    }
}
