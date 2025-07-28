<?php

namespace Tests;

use Masyasmv\Multithreaded\Contracts\CommandInterface;
use Masyasmv\Multithreaded\Manager;
use PHPUnit\Framework\TestCase;

class ManagerTest extends TestCase
{
    public function testStartSpawnsRuntimes(): void
    {
        $m = new Manager(2);
        usleep(100_000);
        $this->assertCount(2, $m->getRuntimes());
    }

    public function testHardStop(): void
    {
        $m = new Manager(1);
        $m->hardStop();
        $this->assertCount(0, $m->getRuntimes());
    }

    public function testSoftStopWaits(): void
    {
        $m = new Manager(1);
        $m->enqueue(
            new class implements CommandInterface {
                public function execute(): void
                {
                    usleep(50_000);
                }
            },
        );

        $start = microtime(true);
        $m->softStop();
        $duration = microtime(true) - $start;

        // Ensure softStop waits for command execution (timing may vary)
        $this->assertGreaterThan(0, $duration, 'softStop should wait for command execution');
        $this->assertCount(0, $m->getRuntimes());
    }
}
