<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Foo;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\Input\StringInput;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\StdOutStreamOutput;
use Symfony\Component\Marshaller\UnmarshallerInterface;

#[AsCommand(name: 'test')]
class TestCommand extends Command
{
    public function __construct(
        private MarshallerInterface $jsonMarshaller,
        private UnmarshallerInterface $jsonUnmarshaller,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $a = new Foo();
        $a->name = 'name';

        $input = new StringInput('{"hel\"lo": "world", "hi": 1, "ok": [1,"e"]}', 2);
        $this->jsonUnmarshaller->unmarshal($input, 'dict');

        // $this->jsonMarshaller->marshal($a, $output = new StdOutStreamOutput());
        // dump((string) $output);

        return Command::SUCCESS;
    }
}
