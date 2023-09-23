<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Json\Tests\CacheWarmer;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Encoder\DataModel\Decode\DataModelBuilder as DecodeDataModelBuilder;
use Symfony\Component\Encoder\DataModel\Encode\DataModelBuilder as EncodeDataModelBuilder;
use Symfony\Component\Encoder\Mapping\PropertyMetadataLoader;
use Symfony\Component\Json\CacheWarmer\TemplateCacheWarmer;
use Symfony\Component\Json\Template\Decode\Template as DecodeTemplate;
use Symfony\Component\Json\Template\Encode\Template as EncodeTemplate;
use Symfony\Component\Json\Tests\Fixtures\Model\ClassicDummy;
use Symfony\Component\Json\Tests\TypeResolverAwareTrait;

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
            sprintf('%s/e95f88b4552266a00e5f1d8f567548e0.decode.json.php', $this->cacheDir),
            sprintf('%s/e95f88b4552266a00e5f1d8f567548e0.decode.json.stream.php', $this->cacheDir),
            sprintf('%s/e95f88b4552266a00e5f1d8f567548e0.encode.json.php', $this->cacheDir),
            sprintf('%s/e95f88b4552266a00e5f1d8f567548e0.encode.json.stream.php', $this->cacheDir),
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
