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

use App\Entity\AbstractEntity;
use App\Traits\CheckSubClassTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract type to display a list of entities.
 *
 * @template T of AbstractEntity
 *
 * @extends AbstractType<EntityType>
 */
abstract class AbstractListEntityType extends AbstractType
{
    use CheckSubClassTrait;

    /**
     * Constructor.
     *
     * @param string $className the entity class name
     *
     * @psalm-param class-string<T> $className
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractEntity class
     */
    public function __construct(private readonly string $className)
    {
        $this->checkSubClass($this->className, AbstractEntity::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'class' => $this->className,
        ]);
    }

    public function getParent(): ?string
    {
        return EntityType::class;
    }
}
