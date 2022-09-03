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
use App\Traits\TranslatorAwareTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Type to display a list of calculation states.
 *
 * @template-extends AbstractListEntityType<CalculationState>
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CalculationStateListType extends AbstractListEntityType implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use TranslatorAwareTrait;

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
            'group_by' => fn (CalculationState $entity): string => $this->translateEditable($entity),
            'query_builder' => static fn (CalculationStateRepository $repository): QueryBuilder => $repository->getQueryBuilderByEditable(),
        ]);
    }

    private function translateEditable(CalculationState $entity): string
    {
        $id = $entity->isEditable() ? 'editable' : 'not_editable';

        return $this->trans("calculationstate.list.$id");
    }
}
