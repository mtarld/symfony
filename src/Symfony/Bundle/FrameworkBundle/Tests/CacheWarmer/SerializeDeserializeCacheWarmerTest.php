<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\CacheWarmer;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\CacheWarmer\SerializerDeserializerCacheWarmer;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony\Component\Serializer\Context\ContextBuilder;
use Symfony\Component\Serializer\Deserialize\Hook\ObjectHookInterface as DeserializeObjectHookInterface;
use Symfony\Component\Serializer\Deserialize\Instantiator\InstantiatorInterface;
use Symfony\Component\Serializer\SerializableResolver\SerializableResolverInterface;
use Symfony\Component\Serializer\Serialize\Hook\ObjectHook;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\ClassicDummy;
use Symfony\Component\Serializer\Tests\Fixtures\Dto\DummyWithGroups;
use Symfony\Component\Serializer\Type\PhpstanTypeExtractor;
use Symfony\Component\Serializer\Type\ReflectionTypeExtractor;

class SerializeDeserializeCacheWarmerTest extends TestCase
{
    private string $templateCacheDir;
    private string $lazyObjectCacheDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->templateCacheDir = sprintf('%s/symfony_serializer_template', sys_get_temp_dir());

        if (is_dir($this->templateCacheDir)) {
            array_map('unlink', glob($this->templateCacheDir.'/*'));
            rmdir($this->templateCacheDir);
        }

        $this->lazyObjectCacheDir = sprintf('%s/symfony_serializer_lazy_object', sys_get_temp_dir());

        if (is_dir($this->lazyObjectCacheDir)) {
            array_map('unlink', glob($this->lazyObjectCacheDir.'/*'));
            rmdir($this->lazyObjectCacheDir);
        }
    }

    public function testWarmUpTemplate()
    {
        $this->cacheWarmer([ClassicDummy::class], ['json'], 32)->warmUp('useless');

        $expectedTemplates = array_map(fn (string $c): string => sprintf('%s/%s.json.php', $this->templateCacheDir, hash('xxh128', $c)), [ClassicDummy::class]);

        $this->assertSame($expectedTemplates, glob($this->templateCacheDir.'/*'));
    }

    public function testWarmUpTemplateWithGroupsVariants()
    {
        $this->cacheWarmer([DummyWithGroups::class], ['json'], 32)->warmUp('useless');

        $expectedTemplates = [
            sprintf('%s/%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class)),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-one')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-two')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-three')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-one_group-two')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-one_group-three')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-three_group-two')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-one_group-three_group-two')),
        ];

        $actualTemplates = glob($this->templateCacheDir.'/*');

        sort($expectedTemplates);
        sort($actualTemplates);

        $this->assertEquals($expectedTemplates, $actualTemplates);
    }

    public function testWarmUpTemplateLogWhenTooManyVariants()
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator([DummyWithGroups::class]));

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('debug');

        $cacheWarmer = new SerializerDeserializerCacheWarmer(
            new ContextBuilder(
                $serializableResolver,
                $this->createStub(InstantiatorInterface::class),
                new ObjectHook(new PhpstanTypeExtractor(new ReflectionTypeExtractor())),
                $this->createStub(DeserializeObjectHookInterface::class),
            ),
            $serializableResolver,
            $this->templateCacheDir,
            $this->lazyObjectCacheDir,
            ['json'],
            3,
            $logger,
        );

        $cacheWarmer->warmUp('useless');
    }

    public function testWarmUpTemplateSliceWhenTooManyVariants()
    {
        $this->cacheWarmer([DummyWithGroups::class], ['json'], 3)->warmUp('useless');

        $expectedTemplates = [
            sprintf('%s/%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class)),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-one')),
            sprintf('%s/%s.%s.json.php', $this->templateCacheDir, hash('xxh128', DummyWithGroups::class), hash('xxh128', 'group-two')),
        ];

        $actualTemplates = glob($this->templateCacheDir.'/*');

        sort($expectedTemplates);
        sort($actualTemplates);

        $this->assertEquals($expectedTemplates, $actualTemplates);
    }

    public function testWarmUpLazyObject()
    {
        $this->cacheWarmer([ClassicDummy::class], ['json'], 32)->warmUp('useless');

        $expectedLazyObjects = array_map(fn (string $c): string => sprintf('%s/%s.php', $this->lazyObjectCacheDir, hash('xxh128', $c)), [ClassicDummy::class]);

        $this->assertSame($expectedLazyObjects, glob($this->lazyObjectCacheDir.'/*'));
    }

    /**
     * @param list<class-string> $serializable
     * @param list<string>       $formats
     */
    private function cacheWarmer(array $serializable, array $formats, int $maxVariants): SerializerDeserializerCacheWarmer
    {
        $serializableResolver = $this->createStub(SerializableResolverInterface::class);
        $serializableResolver->method('resolve')->willReturn(new \ArrayIterator($serializable));

        return new SerializerDeserializerCacheWarmer(
            new ContextBuilder(
                $serializableResolver,
                $this->createStub(InstantiatorInterface::class),
                new ObjectHook(new PhpstanTypeExtractor(new ReflectionTypeExtractor())),
                $this->createStub(DeserializeObjectHookInterface::class),
            ),
            $serializableResolver,
            $this->templateCacheDir,
            $this->lazyObjectCacheDir,
            $formats,
            $maxVariants,
        );
    }
}
