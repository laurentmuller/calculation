<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Form;

use App\Util\Utils;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Base type to use with an entity class.
 *
 * @author Laurent Muller
 *
 * @template T of \App\Entity\AbstractEntity
 */
abstract class AbstractEntityType extends AbstractHelperType
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
     */
    protected function __construct(string $className)
    {
        $this->className = $className;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->className,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getLabelPrefix(): ?string
    {
        $name = \strtolower(Utils::getShortName($this->className));

        return "$name.fields.";
    }
}
