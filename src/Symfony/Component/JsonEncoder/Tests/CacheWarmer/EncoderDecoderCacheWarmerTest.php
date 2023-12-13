<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonEncoder\Tests\CacheWarmer;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\JsonEncoder\CacheWarmer\EncoderDecoderCacheWarmer;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\JsonEncoder\Decode\DecoderGenerator;
use Symfony\Component\JsonEncoder\Encode\EncoderGenerator;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;

class EncoderDecoderCacheWarmerTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $encoderCacheDir;
    private string $decoderCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->encoderCacheDir = sprintf('%s/symfony_json_encoder_encoder', sys_get_temp_dir());
        $this->decoderCacheDir = sprintf('%s/symfony_json_encoder_decoder', sys_get_temp_dir());

        if (is_dir($this->encoderCacheDir)) {
            array_map('unlink', glob($this->encoderCacheDir.'/*'));
            rmdir($this->encoderCacheDir);
        }

        if (is_dir($this->decoderCacheDir)) {
            array_map('unlink', glob($this->decoderCacheDir.'/*'));
            rmdir($this->decoderCacheDir);
        }
    }

    public function testWarmUp()
    {
        $this->cacheWarmer([ClassicDummy::class])->warmUp('useless');

        $this->assertSame([
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.resource.php', $this->encoderCacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.stream.php', $this->encoderCacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.string.php', $this->encoderCacheDir),
        ], glob($this->encoderCacheDir.'/*'));

        $this->assertSame([
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.resource.php', $this->decoderCacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.stream.php', $this->decoderCacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.json.string.php', $this->decoderCacheDir),
        ], glob($this->decoderCacheDir.'/*'));
    }

    /**
     * @param list<class-string> $encodable
     */
    private function cacheWarmer(array $encodable): EncoderDecoderCacheWarmer
    {
        $typeResolver = self::getTypeResolver();

        return new EncoderDecoderCacheWarmer(
            $encodable,
            new EncoderGenerator(new EncodeDataModelBuilder(new PropertyMetadataLoader($typeResolver)), $this->encoderCacheDir),
            new DecoderGenerator(new DecodeDataModelBuilder(new PropertyMetadataLoader($typeResolver)), $this->decoderCacheDir),
            new NullLogger(),
        );
    }
}
