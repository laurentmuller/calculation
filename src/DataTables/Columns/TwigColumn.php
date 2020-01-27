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

namespace App\DataTables\Columns;

use App\DataTables\Tables\AbstractDataTable;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Data column for render cell within a twig template.
 *
 * @property string $template the template name.
 *
 * @author Laurent Muller
 */
class TwigColumn extends AbstractColumn
{
    /**
     * Constructor.
     *
     * @param AbstractDataTable $table   the parent table
     * @param string            $name    the field name
     * @param array             $options the additional options
     */
    public function __construct(AbstractDataTable $table, string $name, array $options = [])
    {
        parent::__construct($table, $name, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function formatValue($value): string
    {
        if (null === $value || null === $this->template) {
            return parent::formatValue($value);
        }

        // $this->table->

        return parent::formatValue($value);
    }

    /**
     * Gets the Twig template.
     *
     * @return string
     */
    public function getTemplate(): ?string
    {
        return $this->template;
    }

    /**
     * Sets the Twig template.
     */
    public function setTemplate(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): self
    {
        parent::configureOptions($resolver);

        $resolver->setRequired('template')
            ->setAllowedTypes('template', 'string');

        return $this;
    }
}
