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
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Abstract type to display a list of entities.
 *
 * @author Laurent Muller
 * @template T of AbstractEntity
 */
abstract class AbstractListEntityType extends AbstractType
{
    /**
     * The entity class name.
     *
     * @psalm-var class-string<T> $className
     */
    protected string $className;

    /**
     * Constructor.
     *
     * @param string $className the entity class name
     * @psalm-param class-string<T> $className
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractEntity class
     */
    public function __construct(string $className)
    {
        if (!\is_subclass_of($className, AbstractEntity::class)) {
            throw new \InvalidArgumentException(\sprintf('Expected argument of type "%s", "%s" given', AbstractEntity::class, $className));
        }

        $this->className = $className;
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'class' => $this->className,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getParent(): string
    {
        return EntityType::class;
    }
}
