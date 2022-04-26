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

namespace App\Form\Category;

use App\Entity\Category;
use App\Form\AbstractListEntityType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of categories grouped by groups.
 *
 * @template-extends AbstractListEntityType<Category>
 */
class CategoryListType extends AbstractListEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(Category::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'choice_label' => 'code',
            'group_by' => 'groupCode',
            'choice_attr' => function (Category $category): array {
                return [
                    'data-group-id' => $category->getGroupId(),
                    'data-group-code' => $category->getGroupCode(),
                ];
            },
            'query_builder' => fn (CategoryRepository $repository): QueryBuilder => $repository->getQueryBuilderByGroup(),
        ]);
    }
}
