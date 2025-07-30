<?php

namespace Masyasmv\Multithreaded\Workers;

use Masyasmv\Multithreaded\Queues\CommandQueue;
use parallel\Runtime;
use parallel\Channel;

class WorkerThread
{
    private Runtime $rt;
    private Channel $in;
    private bool $softStop = false;

    public function __construct(CommandQueue $queue)
    {
        $this->in = $queue->getRawChannel();
        $this->rt = new Runtime();

        $this->rt->run(function (Channel $in) {
            while (true) {
                $cmd = $in->recv(); // блокирует поток
                if ($cmd === null) {
                    // сигнал завершить работу
                    break;
                }
                try {
                    $cmd->execute();
                } catch (\Throwable $e) {
                    // лог/игнорируем
                }
            }
        }, [$this->in]);
    }

    public function hardStop(): void
    {
        // Мгновенно убиваем runtime
        $this->rt->kill();
    }

    public function softStop(): void
    {
        if ($this->softStop) return;
        $this->softStop = true;
        // Посылаем null – сигнал graceful shutdown
        $this->in->send(null);
    }

    public function isRunning(): bool
    {
        return $this->rt->status()['running'];
    }
}