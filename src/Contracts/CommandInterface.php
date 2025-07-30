<?php

namespace Masyasmv\Multithreaded\Contracts;

interface CommandInterface
{
    /** Выполнить команду */
    public function execute(): void;
}