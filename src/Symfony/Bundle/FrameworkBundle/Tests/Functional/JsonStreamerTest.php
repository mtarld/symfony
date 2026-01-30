<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonStreamer\Dto\Dummy;
use Symfony\Bundle\FrameworkBundle\Tests\Functional\app\JsonStreamer\Dto\Height;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\JsonStreamer\StreamerDumper;
use Symfony\Component\JsonStreamer\StreamReaderInterface;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\JsonStreamer\Transformer\DateTimeValueObjectTransformer;
use Symfony\Component\TypeInfo\Type;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
class JsonStreamerTest extends AbstractWebTestCase
{
    protected function setUp(): void
    {
        static::bootKernel(['test_case' => 'JsonStreamer']);
    }

    public function testWrite()
    {
        /** @var StreamWriterInterface $writer */
        $writer = static::getContainer()->get('json_streamer.stream_writer.alias');

        $dummy = new Dummy();
        $dummy->height = new Height(10, 'meters');

        $this->assertSame('{"@name":"DUMMY","range":"10..20","height":"10 meters"}', (string) $writer->write($dummy, Type::object(Dummy::class), [
            DateTimeValueObjectTransformer::FORMAT_KEY => 'Y-m-d',
        ]));
    }

    public function testRead()
    {
        /** @var StreamReaderInterface $reader */
        $reader = static::getContainer()->get('json_streamer.stream_reader.alias');

        $expected = new Dummy();
        $expected->name = 'dummy';
        $expected->range = [0, 1];
        $expected->height = new Height(10, 'meters');

        $this->assertEquals($expected, $reader->read('{"@name": "DUMMY", "range": "0..1", "height": "10 meters"}', Type::object(Dummy::class)));
    }

    public function testWarmupStreamableClasses()
    {
        /** @var Filesystem $fs */
        $fs = static::getContainer()->get('filesystem');

        $streamWritersDir = \sprintf('%s/json_streamer/stream_writer/', static::getContainer()->getParameter('kernel.cache_dir'));

        // clear already created stream writers
        if ($fs->exists($streamWritersDir)) {
            $fs->remove($streamWritersDir);
        }

        static::getContainer()->get('json_streamer.cache_warmer.streamer.alias')->warmUp(static::getContainer()->getParameter('kernel.cache_dir'));

        $this->assertFileExists($streamWritersDir);

        if (!class_exists(StreamerDumper::class)) {
            $this->assertCount(2, glob($streamWritersDir.'/*'));
        } else {
            $this->assertCount(2, glob($streamWritersDir.'/*.php'));
            $this->assertCount(2, glob($streamWritersDir.'/*.php.meta'));
            $this->assertCount(2, glob($streamWritersDir.'/*.php.meta.json'));
        }
    }
}
