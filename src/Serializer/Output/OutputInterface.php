<?php

namespace App\Serializer\Output;

interface OutputInterface extends \Stringable
{
    public function write(string $data): void;

    public function erase(int $count): void;
}
