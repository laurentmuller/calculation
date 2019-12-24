<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
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
     * @see \Symfony\Component\Form\AbstractTypeExtension::buildView()
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
