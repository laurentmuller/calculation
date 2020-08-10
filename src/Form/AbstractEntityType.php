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

namespace App\Form;

use App\Util\Utils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base type to use with an entity class.
 *
 * @author Laurent Muller
 */
abstract class AbstractEntityType extends AbstractHelperType
{
    /**
     * The entity class name.
     *
     * @var string
     */
    protected $className;

    /**
     * Constructor.
     *
     * @param string $className the entity class name
     */
    protected function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->className,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        $name = \strtolower(Utils::getShortName($this->className));

        return "$name.fields.";
    }
}
