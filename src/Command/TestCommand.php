<?php

namespace App\Command;

use App\Dto\Foo;
use App\Serializer\Serializer;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    public function __construct(
        private PropertyInfoExtractorInterface $extractor,
        private Serializer $serializer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = new Foo();
        $a->name = 'name';

        $serializer = $this->serializer->withEncoding('json', 'string');

        dump($serializer->serialize($a));

        return Command::SUCCESS;
    }
}
