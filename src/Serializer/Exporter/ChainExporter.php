<?php

declare(strict_types=1);

namespace App\Serializer\Exporter;

use App\Serializer\Encoder\EncoderInterface;
use App\Serializer\Output\OutputInterface;

final class ChainExporter
{
    public function __construct(
        private iterable $serializers,
        private EncoderInterface $encoder,
    ) {
    }

    public function serialize(mixed $value): OutputInterface
    {
        $type = $this->getType($value);
        $serializer = $this->findSerializer($value, $type);

        return $serializer->serialize($value, $type, $this->encoder, $this->serialize(...));
    }

    private function findSerializer(mixed $value, string $type): Exporter
    {
        foreach ($this->serializers as $serializer) {
            if ($serializer->supports($value, $type)) {
                return $serializer;
            }
        }

        throw new \RuntimeException('Cannot find serializer');
    }

    private function getType(mixed $value): string
    {
        // TODO new TypeGuesserClass?

        $type = get_debug_type($value);
        if (is_object($value)) {
            $type = 'object';
        }

        if ('array' === $type) {
            $type = array_is_list($value) ? 'list' : 'dict';
        }

        return $type;
    }
}
