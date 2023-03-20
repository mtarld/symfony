<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Instantiator;

use Symfony\Component\VarExporter\ProxyHelper;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 6.4
 */
final class LazyInstantiator implements InstantiatorInterface
{
    /**
     * @var array{lazy_class_name: array<string, class-string>}
     */
    private static array $cache = [
        'lazy_class_name' => [],
    ];

    /**
     * @var array<string, bool>
     */
    private static array $lazyClassesLoaded = [];

    public function __construct(
        private readonly string $cacheDir,
    ) {
    }

    public function __invoke(\ReflectionClass $class, array $propertiesValues, array $context): object
    {
        if (!isset(self::$cache['lazy_class_name'][$className = $class->getName()])) {
            /** @var class-string $lazyClassName */
            $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', $className));
            self::$cache['lazy_class_name'][$className] = $lazyClassName;
        }

        if (!isset(self::$lazyClassesLoaded[$className]) && !class_exists(self::$cache['lazy_class_name'][$className])) {
            if (!file_exists($path = sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5($className)))) {
                if (!file_exists($this->cacheDir)) {
                    mkdir($this->cacheDir, recursive: true);
                }

                $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', $className));
                file_put_contents($path, sprintf('class %s%s', $lazyClassName, ProxyHelper::generateLazyGhost($class)));
            }

            eval(file_get_contents(sprintf('%s%s%s.php', $this->cacheDir, \DIRECTORY_SEPARATOR, md5($className))));

            self::$lazyClassesLoaded[$className] = true;
        }

        $lazyGhost = self::$cache['lazy_class_name'][$className]::createLazyGhost($propertiesValues);

        return $lazyGhost;
    }
}
