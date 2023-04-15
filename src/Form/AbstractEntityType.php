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
use App\Utils\StringUtils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Edition type to use within an {@link AbstractEntity} class.
 *
 * @template T of AbstractEntity
 */
abstract class AbstractEntityType extends AbstractHelperType
{
    use CheckSubClassTrait;

    /**
     * The entity class name.
     *
     * @psalm-var class-string<T> $className
     */
    protected string $className;

    /**
     * Constructor.
     *
     * @param class-string<T> $className the entity class name
     *
     * @throws \InvalidArgumentException if the given class name is not a subclass of the AbstractEntity class
     */
    protected function __construct(string $className)
    {
        $this->checkSubClass($className, AbstractEntity::class);
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('data_class', $this->className);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        $name = \strtolower(StringUtils::getShortName($this->className));

        return "$name.fields.";
    }
}
