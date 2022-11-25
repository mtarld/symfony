<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Collection;
use App\Dto\Item;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\MarshallerInterface;

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
        dump($this->marshaller->generate(sprintf('%s<string, %s>', Collection::class, Item::class), 'json'));

        return Command::SUCCESS;
    }
}
