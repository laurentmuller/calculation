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

namespace App\Form\Category;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of category entities.
 *
 * @author Laurent Muller
 */
class CategoryEntityType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'placeholder' => false,
            'choice_label' => 'code',
            'group_by' => 'groupCode',
            'class' => Category::class,
            'query_builder' => function (CategoryRepository $repository) {
                return $repository->getParentCodeSortedBuilder();
            },
            'choice_attr' => function (Category $category) {
                return [
                    'data-code' => $category->getCode(),
                    'data-group-id' => $category->getGroupId(),
                    'data-group-code' => $category->getGroupCode(),
                ];
            },
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
