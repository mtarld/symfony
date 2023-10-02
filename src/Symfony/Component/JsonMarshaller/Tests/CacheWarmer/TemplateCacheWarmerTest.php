<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\JsonMarshaller\Tests\CacheWarmer;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\JsonMarshaller\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\JsonMarshaller\Marshal\DataModel\DataModelBuilder as MarshalDataModelBuilder;
use Symfony\Component\JsonMarshaller\Marshal\Mapping\PropertyMetadataLoader as MarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Marshal\Template\Template as MarshalTemplate;
use Symfony\Component\JsonMarshaller\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\JsonMarshaller\Type\ReflectionTypeExtractor;
use Symfony\Component\JsonMarshaller\Unmarshal\DataModel\DataModelBuilder as UnmarshalDataModelBuilder;
use Symfony\Component\JsonMarshaller\Unmarshal\Mapping\PropertyMetadataLoader as UnmarshalPropertyMetadataLoader;
use Symfony\Component\JsonMarshaller\Unmarshal\Template\Template as UnmarshalTemplate;

class TemplateCacheWarmerTest extends TestCase
{
    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_marshaller_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUpTemplates()
    {
        $this->cacheWarmer([ClassicDummy::class])->warmUp('useless');

        $this->assertSame([
            sprintf('%s/c486b01895febbc4130485acd2c56d11.marshal.json.eager.php', $this->cacheDir),
            sprintf('%s/c486b01895febbc4130485acd2c56d11.marshal.json.lazy.php', $this->cacheDir),
            sprintf('%s/c486b01895febbc4130485acd2c56d11.unmarshal.eager.json.php', $this->cacheDir),
            sprintf('%s/c486b01895febbc4130485acd2c56d11.unmarshal.lazy.json.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    /**
     * @param list<class-string> $marshallable
     */
    private function cacheWarmer(array $marshallable): TemplateCacheWarmer
    {
        return new TemplateCacheWarmer(
            $marshallable,
            new MarshalTemplate(
                new MarshalDataModelBuilder(new MarshalPropertyMetadataLoader(new ReflectionTypeExtractor()), null),
                $this->cacheDir,
            ),
            new UnmarshalTemplate(
                new UnmarshalDataModelBuilder(new UnmarshalPropertyMetadataLoader(new ReflectionTypeExtractor()), null),
                $this->cacheDir,
            ),
            $this->cacheDir,
            new NullLogger(),
        );
    }
}
