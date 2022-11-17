<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Dto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\NullableDataOption;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Context\Option\ValidateDataOption;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\StdoutStreamOutput;
use function Symfony\Component\Marshaller\marshal;
use function Symfony\Component\Marshaller\marshal_generate;

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
        // $this->generateFunction();
        $this->generate();

        // $this->marshal();
        // $this->marshalFunction();

        return Command::SUCCESS;
    }

    private function generateFunction(): void
    {
        $context = [
            'cache_dir' => 'var/cache/dev/marshaller',
        ];

        echo marshal_generate('array<array<string>>', 'json', $context);
    }

    private function generate(): void
    {
        $object = new Dto();

        $context = new Context();
        // $context = new Context(new TypeOption('int'));
        // $context = new Context(new NullableDataOption());
        // $context = new Context(new ValidateDataOption());

        echo $this->marshaller->generate('?'.Dto::class, 'json', $context);
    }

    private function marshal(): void
    {
        $output = new StdoutStreamOutput();

        $context = new Context();
        // $context = new Context(new TypeOption('int'));
        // $context = new Context(new NullableDataOption());
        // $context = new Context(new ValidateDataOption());

        $this->marshaller->marshal($object, 'json', $output, $context);
    }

    private function marshalFunction(): void
    {
        $object = new Dto();
        $resource = fopen('php://stdout', 'wb');

        $context = [
            'cache_dir' => 'var/cache/dev/marshaller',
            'hooks' => [
                'property' => static function (\ReflectionProperty $property, string $accessor, string $format, array $context): string {
                    $context['accessor'] = $accessor;
                    $context['enclosed'] = false;

                    unset($context['hooks']['string']);

                    return marshal_generate($context['property_type'], $format, $context);
                },
                'string' => static function (string $type, string $accessor, string $format, array $context): string {
                    $context['accessor'] = $accessor;
                    $context['enclosed'] = false;

                    unset($context['hooks']['string']);

                    return marshal_generate($type, $format, $context);
                },
            ],
        ];

        marshal($object, $resource, 'json', $context);
    }
}
