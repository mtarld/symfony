<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Tests\Internal;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Marshaller\Exception\InvalidConstructorArgumentException;
use Symfony\Component\Marshaller\Exception\UnexpectedTypeException;
use Symfony\Component\Marshaller\Internal\Unmarshal\Instantiator;

class InstantiatorTest extends TestCase
{
    public function testInstantiateWithoutConstructor()
    {
        $instance = (new Instantiator())(new \ReflectionClass(DummyWithoutConstructor::class), [], []);

        $this->assertInstanceOf(DummyWithoutConstructor::class, $instance);
    }

    public function testInstantiateWithoutPublicConstructor()
    {
        $instance = (new Instantiator())(new \ReflectionClass(DummyWithoutPublicConstructor::class), [], []);

        $this->assertInstanceOf(DummyWithoutPublicConstructor::class, $instance);
        $this->assertFalse($instance->updated);
    }

    public function testInstantiateWithConstructorWithDefaultValues()
    {
        $instance = (new Instantiator())(new \ReflectionClass(DummyWithDefaultConstructorValues::class), [], []);

        $this->assertInstanceOf(DummyWithDefaultConstructorValues::class, $instance);
        $this->assertTrue($instance->foo);
        $this->assertFalse($instance->bar);
    }

    public function testInstantiateWithConstructorWithNullableValues()
    {
        $instance = (new Instantiator())(new \ReflectionClass(DummyWithNullableConstructorValues::class), [], []);

        $this->assertInstanceOf(DummyWithNullableConstructorValues::class, $instance);
        $this->assertNull($instance->foo);
    }

    public function testInstantiateWithInvalidConstructorArgumentThrow()
    {
        $this->expectException(InvalidConstructorArgumentException::class);

        (new Instantiator())(new \ReflectionClass(DummyWithRequiredConstructorArguments::class), [], []);
    }

    public function testInstantiateWithInvalidConstructorArgumentCollectError()
    {
        $context = ['collect_errors' => true];
        $errors = &$context['collected_errors'];

        $instance = (new Instantiator())(new \ReflectionClass(DummyWithRequiredConstructorArguments::class), [], $context);

        $this->assertCount(2, $context['collected_errors']);
        $this->assertContainsOnlyInstancesOf(InvalidConstructorArgumentException::class, $context['collected_errors']);

        $this->assertInstanceOf(DummyWithRequiredConstructorArguments::class, $instance);
    }

    public function testSetProperty()
    {
        $instance = (new Instantiator())(new \ReflectionClass(DummyWithProperty::class), ['updated' => fn () => true], []);

        $this->assertTrue($instance->updated);
    }

    public function testSetPropertyInvalidTypeThrow()
    {
        $this->expectException(UnexpectedTypeException::class);

        (new Instantiator())(new \ReflectionClass(DummyWithProperty::class), ['updated' => fn () => new \DateTimeImmutable()], []);
    }

    public function testSetPropertyInvalidTypeCollectError()
    {
        $context = ['collect_errors' => true];
        $errors = &$context['collected_errors'];

        $instance = (new Instantiator())(new \ReflectionClass(DummyWithProperty::class), ['updated' => fn () => new \DateTimeImmutable()], $context);

        $this->assertCount(1, $context['collected_errors']);
        $this->assertContainsOnlyInstancesOf(UnexpectedTypeException::class, $context['collected_errors']);

        $this->assertInstanceOf(DummyWithProperty::class, $instance);
        $this->assertFalse($instance->updated);
    }
}

class DummyWithoutConstructor
{
}

class DummyWithoutPublicConstructor
{
    public bool $updated = false;

    protected function __construct()
    {
        $this->updated = true;
    }
}

class DummyWithDefaultConstructorValues
{
    public function __construct(
        public bool $foo = true,
        public ?bool $bar = false,
    ) {
    }
}

class DummyWithNullableConstructorValues
{
    public function __construct(
        public ?bool $foo,
    ) {
    }
}

class DummyWithRequiredConstructorArguments
{
    public function __construct(
        public bool $required,
        public bool $requiredAsWell,
    ) {
    }
}

class DummyWithProperty
{
    public function __construct(
        public bool $updated = false,
    ) {
    }
}
