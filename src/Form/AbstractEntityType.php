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
use App\Utils\StringUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to edit a <code>EntityInterface</code> class.
 *
 * @template TEntity of EntityInterface
 */
abstract class AbstractEntityType extends AbstractHelperType
{
    use CheckSubClassTrait;

    /**
     * The entity class name.
     *
     * @psalm-var class-string<TEntity> $className
     */
    protected string $className;

    /**
     * @param class-string<TEntity> $className the entity class name
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the entity interface
     */
    protected function __construct(string $className)
    {
        $this->checkSubClass($className, EntityInterface::class);
        $this->className = $className;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', $this->className);
    }

    protected function getLabelPrefix(): ?string
    {
        $name = \strtolower(StringUtils::getShortName($this->className));

        return "$name.fields.";
    }
}
