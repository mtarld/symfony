<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Encoder;

use Symfony\Component\Serializer\Context\Context;

/**
 * Adds the support of an extra $context parameter for the supportsEncoding method.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface ContextAwareEncoderInterface extends EncoderInterface
{
    /**
     * {@inheritdoc}
     *
     * @param Context|null $context Options that encoders have access to
     */
    public function supportsEncoding(string $format /*, Context $context = null */): bool;
}
