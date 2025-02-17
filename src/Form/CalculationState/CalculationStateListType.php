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

namespace App\Form\CalculationState;

use App\Entity\CalculationState;
use App\Form\AbstractListEntityType;
use App\Repository\CalculationStateRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to display a list of calculation states.
 *
 * @template-extends AbstractListEntityType<CalculationState>
 */
class CalculationStateListType extends AbstractListEntityType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct(CalculationState::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'code',
            'group_by' => fn (CalculationState $entity): string => $this->translateEditable($entity),
            'query_builder' => static fn (CalculationStateRepository $repository): QueryBuilder => $repository->getQueryBuilderByEditable(),
        ]);
    }

    private function translateEditable(CalculationState $entity): string
    {
        return $this->translator->trans(\sprintf('calculationstate.list.editable_%d', (int) $entity->isEditable()));
    }
}
