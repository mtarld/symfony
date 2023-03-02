<?php

/*
 * This file is part of the Symfony package.
 * (c) Fabien Potencier <fabien@symfony.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Marshaller\Instantiator;

use Symfony\Component\Marshaller\Exception\RuntimeException;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 */
final class LazyInstantiator implements InstantiatorInterface
{
    /**
     * @var array<string, class-string>
     */
    private static array $lazyClassesCache = [];

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
        if (!isset(self::$lazyClassesCache[$className = $class->getName()])) {
            /** @var class-string $lazyClassName */
            $lazyClassName = sprintf('%sGhost', preg_replace('/\\\\/', '', $className));
            self::$lazyClassesCache[$className] = $lazyClassName;
        }

        if (!isset(self::$lazyClassesLoaded[$className]) && !class_exists(self::$lazyClassesCache[$className])) {
            if (!file_exists(sprintf('%s/%s.php', $this->cacheDir, md5($className)))) {
                throw new RuntimeException(sprintf('Cannot find any lazy ghost class for "%s"', $className));
            }

            eval(file_get_contents(sprintf('%s/%s.php', $this->cacheDir, md5($className))));

            self::$lazyClassesLoaded[$className] = true;
        }

        $lazyGhost = self::$lazyClassesCache[$className]::createLazyGhost($propertiesValues);

        return $lazyGhost;
    }
}
