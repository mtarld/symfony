<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Foo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\NewSerializer\Output\MemoryStreamOutput;
use Symfony\Component\NewSerializer\SerializerInterface;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    public function __construct(
        private SerializerInterface $serializer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = new Foo();
        $a->name = 'name';

        $serializer = $this->serializer->withOutput(new MemoryStreamOutput());
        dump((string) $serializer->serialize($a, 'json'));

        return Command::SUCCESS;
    }
}
