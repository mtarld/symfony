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
use Symfony\Component\JsonEncoder\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\JsonEncoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\JsonEncoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\JsonEncoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\JsonEncoder\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\JsonEncoder\Template\Encode\Template as EncodeTemplate;
use Symfony\Component\JsonEncoder\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\JsonEncoder\Tests\TypeResolverAwareTrait;

class TemplateCacheWarmerTest extends TestCase
{
    use TypeResolverAwareTrait;

    private string $cacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDir = sprintf('%s/symfony_json_template', sys_get_temp_dir());

        if (is_dir($this->cacheDir)) {
            array_map('unlink', glob($this->cacheDir.'/*'));
            rmdir($this->cacheDir);
        }
    }

    public function testWarmUpTemplates()
    {
        $this->cacheWarmer([ClassicDummy::class])->warmUp('useless');

        $this->assertSame([
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.decode.json.resource.php', $this->cacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.decode.json.stream.php', $this->cacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.decode.json.string.php', $this->cacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.encode.json.resource.php', $this->cacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.encode.json.stream.php', $this->cacheDir),
            sprintf('%s/d147026bb5d25e5012afcdc1543cf097.encode.json.string.php', $this->cacheDir),
        ], glob($this->cacheDir.'/*'));
    }

    /**
     * @param list<class-string> $encodable
     */
    private function cacheWarmer(array $encodable): TemplateCacheWarmer
    {
        $typeResolver = self::getTypeResolver();

        return new TemplateCacheWarmer(
            $encodable,
            new EncodeTemplate(new EncodeDataModelBuilder(new PropertyMetadataLoader($typeResolver)), $this->cacheDir),
            new DecodeTemplate(new DecodeDataModelBuilder(new PropertyMetadataLoader($typeResolver)), $this->cacheDir),
            $this->cacheDir,
            new NullLogger(),
        );
    }
}
