<?php

declare(strict_types=1);

namespace App\Command;

use App\Dto\Dto;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\PropertyNameFormatterOption;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;
use Symfony\Component\Marshaller\Context\Option\TypeValueFormatterOption;
use Symfony\Component\Marshaller\Context\Option\ValueFormattersOption;
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
        // $this->generate();

        $this->marshal();
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
        $context = new Context(new TypeOption('int'));
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

        // $valueFormatters = new ValueFormattersOption([
        //     'App\\Dto\\Dto::$id' => $this->test(...),
        // ]);

        $propertyNameFormatter = new PropertyNameFormatterOption([
            Dto::class => [
                'id' => $this->test2(...),
            ],
        ]);

        $propertyValueFormatter = new PropertyValueFormatterOption([
            Dto::class => [
                'id' => $this->test(...),
            ],
        ]);

        $valueFormatter = new TypeValueFormatterOption([
            'string' => static function (string $value, array $context): string {
                return strtoupper($value);
            },
        ]);

        $context = new Context($propertyNameFormatter, $propertyValueFormatter, $valueFormatter);

        $this->marshaller->marshal(new Dto(), 'json', $output, $context);
    }

    public function test(int $value, array $context): string
    {
        return sprintf('/foo/bar/%d', $value);
    }

    public function test2(string $name, array $context): string
    {
        return strtoupper($name);
    }

    // private function test(\ReflectionProperty $property, string $accessor, string $format, array $context): string
    // {
    //     $key = str_repeat(' ', 4 * $context['indentation_level'])
    //         .'fwrite($resource, \'"@id":\');'
    //         .PHP_EOL;
    //
    //     $context['enclosed'] = false;
    //     $context['accessor'] = sprintf('$context[\'closures\'][\'prepareIri\'](%s)', $accessor);
    //
    //     $value = marshal_generate('string', $format, $context);
    //
    //     return $key.$value;
    // }

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

                    return $context['property_name_generator']($property, $context['property_separator'], $context)
                        .marshal_generate($context['property_type'], $format, $context);
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
