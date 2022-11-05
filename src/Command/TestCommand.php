<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Dto;
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
        $resource = fopen('php://stdout', 'wb');

        $object = new Dto();
        $context = [
            'cache_path' => '/tmp/marshaller',
            'max_depth' => 1,
            'hooks' => [
                'App\\Dto\\Dto::$array' => static function (\ReflectionProperty $property, string $objectAccessor, array $context): string {
                    return '    /** ok **/' . PHP_EOL;
                }
            ],
        ];

        // marshal(new Dto(), $resource, 'json', $context);

        dd(json_marshal_generate(new \ReflectionClass($object), $context));


        return Command::SUCCESS;
    }
}
