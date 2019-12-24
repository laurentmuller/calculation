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

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Extends the FileType to use within the FileInput plugin.
 *
 * @author Laurent Muller
 */
class FileTypeExtension extends BaseFileTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FileType::class];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateAttributes(FormInterface $form, array &$attributes, array &$options): void
    {
        // merge options from parent (VichFileType or VichImageType)
        if ($parent = $form->getParent()) {
            $configuration = $parent->getConfig();
            foreach (['placeholder', 'maxfiles', 'maxsize'] as $option) {
                $this->updateOption($configuration, $options, $option);
            }
        }

        // default
        parent::updateAttributes($form, $attributes, $options);
    }

    /**
     * Update an option.
     *
     * @param FormConfigInterface $configuration the form configuration to get value from
     * @param array               $options       the options array to update
     * @param string              $name          the option name to search for
     */
    private function updateOption(FormConfigInterface $configuration, array &$options, string $name): self
    {
        if ($configuration->hasOption($name)) {
            $options[$name] = $configuration->getOption($name);
        }

        return $this;
    }
}
