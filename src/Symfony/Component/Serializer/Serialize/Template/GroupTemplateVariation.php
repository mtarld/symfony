<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Serializer\Serialize\Template;

/**
 * @author Mathias Arlaud <mathias.arlaud@gmail.com>
 *
 * @experimental in 7.0
 */
readonly class GroupTemplateVariation extends TemplateVariation
{
    public function __construct(string $group)
    {
        parent::__construct('group', $group);
    }

    public function updateContext(array $context): array
    {
        $context['groups'][] = $this->value;
        $context['groups'] = array_values(array_unique($context['groups']));

        return $context;
    }
}
