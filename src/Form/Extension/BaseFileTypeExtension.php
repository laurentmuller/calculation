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
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base extension for FileType.
 *
 * @author Laurent Muller
 */
abstract class BaseFileTypeExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $this->updateAttributes($form, $view->vars['attr'], $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        // the number of files
        $resolver->setDefined('maxfiles')
            ->setAllowedTypes('maxfiles', 'integer');

        // the size of each file
        $resolver->setDefined('maxsize')
            ->setAllowedTypes('maxsize', ['integer', 'string']);

        // the total size of all files
        $resolver->setDefined('maxsizetotal')
            ->setAllowedTypes('maxsizetotal', ['integer', 'string']);

        // the place holder
        $resolver->setDefined('placeholder')
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setDefault('placeholder', 'filetype.placeholder');
    }

    /**
     * Normalize the given size.
     *
     * @param string|int $size the size to normalize
     *
     * @return int the normalized size
     *
     * @throws InvalidOptionsException if the $size can not be parsed
     *
     * @see https://symfony.com/doc/current/reference/constraints/File.html#maxsize
     */
    protected function normalizeSize($size): ?int
    {
        if (empty($size)) {
            return null;
        }

        $factors = [
            'k' => 1000,
            'ki' => 1 << 10,
            'm' => 1000000,
            'mi' => 1 << 20,
        ];

        $matches = [];
        if (\ctype_digit((string) $size)) {
            return (int) $size;
        }
        if (\preg_match('/^(\d++)(' . \implode('|', \array_keys($factors)) . ')$/i', $size, $matches)) {
            return $matches[1] * $factors[\strtolower($matches[2])];
        }
        throw new InvalidOptionsException("\"{$size}\" is not a valid size.");
    }

    /**
     * Updates attributes.
     *
     * @param FormInterface $form       the form
     * @param array         $attributes the attributes to update
     * @param array         $options    the options
     */
    protected function updateAttributes(FormInterface $form, array &$attributes, array &$options): void
    {
        if (isset($options['placeholder'])) {
            $attributes['placeholder'] = $options['placeholder'];
        }
        if (isset($options['maxfiles'])) {
            $value = (int) ($options['maxfiles']);
            if ($value > 1) {
                $attributes['maxfiles'] = $value;
            }
        }
        if (isset($options['maxsize'])) {
            $value = self::normalizeSize($options['maxsize']);
            if ($value && $value > 0) {
                $attributes['maxsize'] = $value;
            }
        }
        if (isset($options['maxsizetotal'])) {
            $value = self::normalizeSize($options['maxsizetotal']);
            if ($value && $value > 0) {
                $attributes['maxsizetotal'] = $value;
            }
        }
    }
}
