<?php

declare(strict_types=1);

namespace Symfony\Component\Marshaller\Context;

use Symfony\Component\Marshaller\Metadata\ClassMetadataFactory;

final class ContextDeclinationResolver
{
    /**
     * @param iterable<OptionDeclinationInterface> $optionsDeclinations
     */
    public function __construct(
        private readonly ClassMetadataFactory $classMetadataFactory,
        private iterable $optionDeclinations,
        private readonly int $maxDeclination,
    ) {
    }

    /**
     * @return list<Context>
     */
    public function resolve(\ReflectionClass $class): array
    {
        $classMetadata = $this->classMetadataFactory->forClass($class, new Context());

        /** @var list<array{type: class-string<OptionDeclinationInterface>, value: string}> */
        $declinations = [];
        foreach ($this->optionDeclinations as $optionDeclination) {
            array_push(
                $declinations,
                ...array_map(fn (string $d): array => ['type' => $optionDeclination::class, 'value' => $d], $optionDeclination::resolve($classMetadata)),
            );
        }

        $cartesianDeclinations = $this->cartesianProduct($declinations);

        $this->preventTooManyDeclinations($cartesianDeclinations);

        return $this->buildContexts($cartesianDeclinations);
    }

    /**
     * @param list<array{type: string, value: string}> $groups
     *
     * @return list<list<array{type: string, value: string}>>
     */
    private function preventTooManyDeclinations(array $cartesianDeclination): void
    {
        if (1 + count($cartesianDeclination) > $this->maxDeclination) {
            throw new \LogicException('Too many declinations');
        }
    }

    /**
     * @param list<array{type: class-string<ContextDeclinationInterface>, value: string}> $declinations
     *
     * @return list<list<array{type: class-string<ContextDeclinationInterface>, value: string}>>
     */
    private function cartesianProduct(array $declinations): array
    {
        $cartesianDeclinations = [[]];

        foreach ($declinations as $declination) {
            foreach ($cartesianDeclinations as $combination) {
                array_push($cartesianDeclinations, array_merge([$declination], $combination));
            }
        }

        array_shift($cartesianDeclinations);

        return $cartesianDeclinations;
    }

    /**
     * @param list<list<array{type: class-string<ContextDeclinationInterface>, value: string}>> $declinations
     *
     * @return list<Context>
     */
    private function buildContexts(array $declinations): array
    {
        /**
         * @param list<array{type: class-string<ContextDeclinationInterface>, value: string}> $options
         *
         * @return array<class-string<ContextDeclinationInterface>, list<string>>
         */
        $groupOptionValues = static function (array $options): array {
            $result = [];

            foreach ($options as $option) {
                if (!isset($result[$option['type']])) {
                    $result[$option['type']] = [];
                }

                $result[$option['type']][] = $option['value'];
            }

            return $result;
        };

        $contexts = [];

        foreach ($declinations as $declination) {
            // TODO OptionDeclinationInterface
            $options = [];
            foreach ($groupOptionValues($declination) as $optionDeclination => $values) {
                $options[] = $optionDeclination::createOption($values);
            }

            $contexts[] = new Context(...$options);
        }

        return $contexts;
    }
}
