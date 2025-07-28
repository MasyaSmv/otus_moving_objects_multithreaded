<?php

namespace Masyasmv\Multithreaded\Queues;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use parallel\Channel;

class CommandQueue
{
    private Channel $channel;

    public function __construct()
    {
        // Используем неограниченный буфер
        $this->channel = Channel::make('commands-' . spl_object_id($this), Channel::Infinite);
    }

    public function send(CommandInterface $cmd): void
    {
        $this->channel->send($cmd);
    }

    public function receive(): CommandInterface|null
    {
        return $this->channel->recv(); // блокируется, пока не получит
    }

    public function getRawChannel(): Channel
    {
        return $this->channel;
    }
}