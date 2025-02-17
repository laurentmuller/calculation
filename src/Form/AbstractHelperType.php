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

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Abstract type to use within the <code>FormHelper</code>.
 *
 * @extends AbstractType<mixed>
 */
abstract class AbstractHelperType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = $this->createFormHelper($builder);
        $this->addFormFields($helper);
    }

    /**
     * Adds the form fields within the given helper.
     */
    abstract protected function addFormFields(FormHelper $helper): void;

    /**
     * Creates the form helper.
     *
     * @psalm-param FormBuilderInterface $builder
     *
     * @phpstan-param FormBuilderInterface<mixed> $builder
     */
    protected function createFormHelper(FormBuilderInterface $builder): FormHelper
    {
        return new FormHelper($builder, $this->getLabelPrefix());
    }

    /**
     * Gets the label prefix.
     *
     * If the prefix is not null, the label is automatically added when the field property is set.
     */
    protected function getLabelPrefix(): ?string
    {
        return null;
    }
}
