<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Dto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\StdoutStreamOutput;

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
        $object = new Dto();

        $this->polyfill($object);
        // $this->component($object);

        return Command::SUCCESS;
    }

    private function component(object $object): void
    {
        $output = new StdoutStreamOutput();

        $this->marshaller->marshal($object, 'json', $output);
    }

    private function polyfill(object $object): void
    {
        $resource = fopen('php://stdout', 'wb');

        $context = [
            'cache_path' => '/tmp/marshaller',
            'max_depth' => 1,
            'hooks' => [
                'App\\Dto\\Dto::$array' => static function (\ReflectionProperty $property, string $objectAccessor, array $context): string {
                    $name = $context['propertyNameGenerator']($property, $context);
                    $value = $context['fwrite']("'[]'", $context);

                    return $name.$value;
                }
            ],
        ];

        dump(marshal_generate(new \ReflectionClass($object), 'json', $context));

        marshal($object, $resource, 'json', $context);
    }
}
