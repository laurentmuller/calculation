<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
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
 * Extends URL type by adding the default protocol as data attribute.
 *
 * @author Laurent Muller
 */
class UrlTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     *
     * @psalm-suppress MixedArrayAssignment
     * @psalm-suppress PropertyTypeCoercion
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($options['default_protocol'])) {
            $view->vars['attr']['data-protocol'] = $options['default_protocol'];
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [UrlType::class];
    }
}
