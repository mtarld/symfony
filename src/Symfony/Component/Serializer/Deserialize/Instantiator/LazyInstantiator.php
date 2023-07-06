<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Deserialize\Instantiator;

use Symfony\Component\Serializer\Deserialize\PropertyConfigurator\DeserializePropertyConfiguration;
use Symfony\Component\Serializer\Deserialize\PropertyConfigurator\DeserializePropertyConfiguratorInterface;
use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
final class LazyInstantiator implements InstantiatorInterface
{
    /**
     * @var array{reflection: \ReflectionClass<object>, lazy_class_name: array<string, class-string>}
     */
    private static array $cache = [
        'reflection' => [],
        'lazy_class_name' => [],
        'lazy_classes_loaded' => [],
    ];

    /**
     * @var array<class-string, true>
     */
    private static array $lazyClassesLoaded = [];

    public function __construct(
        private readonly DeserializePropertyConfiguratorInterface $propertyConfigurator,
        private readonly string $cacheDir,
    ) {
    }

    public function instantiate(string $className, array $properties, array $context): object
    {
        $values = array_map(fn (DeserializePropertyConfiguration $c) => $c->value, $this->propertyConfigurator->configure($className, $properties, $context));

        $reflection = self::$cache['reflection'][$className] ??= new \ReflectionClass($className);
        self::$cache['lazy_class_name'][$className] ??= sprintf('%sGhost', preg_replace('/\\\\/', '', $className));

        if (isset(self::$lazyClassesLoaded[$className]) && class_exists(self::$cache['lazy_class_name'][$className])) {
            return self::$cache['lazy_class_name'][$className]::createLazyGhost($values);
        }

        if (!file_exists($path = sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className)))) {
            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir, recursive: true);
            }

            $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', $className));
            file_put_contents($path, sprintf('class %s%s', $lazyClassName, ProxyHelper::generateLazyGhost($reflection)));
        }

        eval(file_get_contents(sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className))));

        self::$lazyClassesLoaded[$className] = true;

        return self::$cache['lazy_class_name'][$className]::createLazyGhost($values);
    }
}
