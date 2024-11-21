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

use App\Interfaces\EntityInterface;
use App\Traits\CheckSubClassTrait;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of code>EntityInterface</code> class.
 *
 * @template TEntity of EntityInterface
 *
 * @extends AbstractType<EntityType>
 */
abstract class AbstractListEntityType extends AbstractType
{
    use CheckSubClassTrait;

    /**
     * @psalm-param class-string<TEntity> $className
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractEntity class
     */
    public function __construct(private readonly string $className)
    {
        $this->checkSubClass($this->className, EntityInterface::class);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'class' => $this->className,
        ]);
    }

    public function getParent(): string
    {
        return EntityType::class;
    }
}
