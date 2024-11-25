<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Extends the URL type by adding the default protocol as the data attribute.
 *
 * @extends AbstractTypeExtension<UrlType>
 */
class UrlTypeExtension extends AbstractTypeExtension
{
    /**
     * @psalm-param array{default_protocol?: string, ...} $options
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['default_protocol'])) {
            $protocol = $options['default_protocol'];
            $view->vars['attr'] = \array_replace($view->vars['attr'], ['data-protocol' => $protocol]);
        }
    }

    public static function getExtendedTypes(): iterable
    {
        return [UrlType::class];
    }
}
