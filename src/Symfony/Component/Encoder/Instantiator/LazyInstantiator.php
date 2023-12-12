<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Encoder\Instantiator;

use Symfony\Component\VarExporter\ProxyHelper;

/**
 * Instantiates a new $className lazy ghost {@see Symfony\Component\VarExporter\LazyGhostTrait}.
 *
 * The $className class must not final.
 *
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental
 */
final class LazyInstantiator implements LazyInstantiatorInterface
{
    /**
     * @var array{reflection: array<class-string, \ReflectionClass<object>>, lazy_class_name: array<class-string, class-string>}
     */
    private static array $cache = [
        'reflection' => [],
        'lazy_class_name' => [],
    ];

    /**
     * @var array<class-string, true>
     */
    private static array $lazyClassesLoaded = [];

    public function __construct(
        private readonly string $cacheDir,
    ) {
    }

    public function instantiate(string $className, array $properties): object
    {
        $reflection = self::$cache['reflection'][$className] ??= new \ReflectionClass($className);
        $lazyClassName = self::$cache['lazy_class_name'][$className] ??= sprintf('%sGhost', preg_replace('/\\\\/', '', $className));

        if (isset(self::$lazyClassesLoaded[$className]) && class_exists($lazyClassName)) {
            return $lazyClassName::createLazyGhost($properties);
        }

        if (!file_exists($path = sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className)))) {
            if (!file_exists($this->cacheDir)) {
                mkdir($this->cacheDir, recursive: true);
            }

            $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', $className));

            file_put_contents($path, sprintf('<?php class %s%s', $lazyClassName, ProxyHelper::generateLazyGhost($reflection)));
        }

        require_once sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, hash('xxh128', $className));

        self::$lazyClassesLoaded[$className] = true;

        return $lazyClassName::createLazyGhost($properties);
    }
}
