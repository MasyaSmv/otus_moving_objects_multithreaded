<?php

namespace Masyasmv\Multithreaded\Commands;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use Masyasmv\Multithreaded\Manager;

class SoftStopCommand implements CommandInterface
{
    public function __construct(private Manager $mgr)
    {
    }

    public function execute(): void
    {
        error_log("[SostStopCommand] execute()");
        $this->mgr->softStop();
        error_log("[SoftStopCommand] done()");
    }
}