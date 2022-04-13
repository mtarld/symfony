<?php

namespace App\Command;

use App\Serializer\Serde;
use App\Dto\Foo;
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
        private Serde $serializer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = new Foo();
        $a->name = 'name';

        dump((string) $this->serializer->serialize($a, 'json', 'string'));
        dump((string) $this->serializer->serialize(['a' => 'b'], 'json', 'string'));
        dump((string) $this->serializer->serialize([1, 2], 'json', 'string'));

        return Command::SUCCESS;
    }
}
