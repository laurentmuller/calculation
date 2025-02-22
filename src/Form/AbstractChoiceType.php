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
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * An abstract choice type.
 *
 * Subclass must override the <code>getChoices()</code> function.
 *
 * @extends AbstractType<ChoiceType>
 */
abstract class AbstractChoiceType extends AbstractType
{
    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('choices', $this->getChoices());
    }

    #[\Override]
    public function getParent(): string
    {
        return ChoiceType::class;
    }

    /**
     * Gets the choice array.
     *
     * @return array an array, where the array key is the item's label, and the array value is the item's value
     */
    abstract protected function getChoices(): array;
}
