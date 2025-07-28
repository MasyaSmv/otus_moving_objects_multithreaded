<?php

namespace Masyasmv\Multithreaded\Commands;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use Masyasmv\Multithreaded\Manager;

class HardStopCommand implements CommandInterface
{
    public function __construct(private Manager $mgr)
    {
    }

    public function execute(): void
    {
        error_log("[HardStopCommand] execute()");
        $this->mgr->hardStop();
        error_log("[HardStopCommand] done()");
    }
}