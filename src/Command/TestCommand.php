<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Dto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\HookOption;
use Symfony\Component\Marshaller\Context\Option\HooksOption;
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

        // $this->generate();
        // $this->polyfill($object);
        $this->component($object);

        return Command::SUCCESS;
    }

    private function generate(): void
    {
        $resource = fopen('php://stdout', 'wb');
        $context = [
            'type' => 'array<array<string>>',
            'cache_path' => 'var/cache/dev/marshaller',
        ];
        marshal([['a', 'b'], ['c', 'd']], $resource, 'json', $context);

        // dump(marshal_generate('array<?array<int, array<bool>>>', 'json', $context));
    }

    private function component(object $object): void
    {
        $output = new StdoutStreamOutput();

        $context = new Context();
        $context = new Context(new HooksOption(['App\\Dto\\Dto::$int' => $this->test(...)]));

        $this->marshaller->marshal($object, 'json', $output, $context);
    }

    private function test(\ReflectionProperty $p, string $accessor, string $format, array $context): string
    {
        return '';
    }

    private function polyfill(object $object): void
    {
        $resource = fopen('php://stdout', 'wb');

        $context = [
            'cache_path' => 'var/cache/dev/marshaller',
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, string $format, array $context): string {
                    $context['main_accessor'] = $accessor;
                    $context['enclosed'] = false;

                    unset($context['hooks']['string']);

                    return marshal_generate($type, $format, $context);
                },
                'string' => static function (string $type, string $accessor, string $format, array $context): string {
                    $context['main_accessor'] = $accessor;
                    $context['enclosed'] = false;

                    unset($context['hooks']['string']);

                    return marshal_generate($type, $format, $context);
                },
            ],
        ];

        marshal($object, $resource, 'json', $context);
    }
}
