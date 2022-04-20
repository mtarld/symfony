<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Foo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\StdOutStreamOutput;
use Symfony\Component\Marshaller\Output\TempStreamOutput;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    public function __construct(
        private MarshallerInterface $jsonMarshaller,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = new Foo();
        $a->name = 'name';

        $this->jsonMarshaller->marshal($a, new StdOutStreamOutput());

        $this->jsonMarshaller->marshal($a, $output = new TempStreamOutput());
        dump((string) $output);

        return Command::SUCCESS;
    }
}
