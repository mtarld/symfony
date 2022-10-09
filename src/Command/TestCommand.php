<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Bar;
use App\Dto\Foo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\DepthOption;
use Symfony\Component\Marshaller\Context\Option\GroupsOption;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\StdoutStreamOutput;
use Symfony\Component\Marshaller\Output\TempStreamOutput;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    public function __construct(
        private MarshallerInterface $marshaller,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $context = new Context(new DepthOption(10, true));
        // $context = new Context();

        $bar = new Bar();
        $bar->foos[] = new Foo();

        $foo = new Foo();
        // $foo->obj = $bar;

        $this->marshaller->marshal($foo, new StdoutStreamOutput());

        return Command::SUCCESS;
    }

    private function testMarshal(): void
    {
    }
}
