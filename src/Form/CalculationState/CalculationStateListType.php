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

namespace App\Form\CalculationState;

use App\Entity\CalculationState;
use App\Form\AbstractListEntityType;
use App\Repository\CalculationStateRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of calculation states.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractListEntityType<CalculationState>
 */
class CalculationStateListType extends AbstractListEntityType
{
    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct(CalculationState::class);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'code',
            'query_builder' => function (CalculationStateRepository $repository): QueryBuilder {
                return $repository->getSortedBuilder();
            },
        ]);
    }
}
