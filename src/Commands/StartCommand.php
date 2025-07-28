<?php

namespace Masyasmv\Multithreaded\Commands;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use Masyasmv\Multithreaded\Manager;

class StartCommand implements CommandInterface
{
    public function __construct(private Manager $mgr)
    {
    }

    public function execute(): void
    { /* ничего: старт происходит в конструкторе */
    }
}