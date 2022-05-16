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
use App\Traits\TranslatorTrait;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to display a list of calculation states.
 *
 * @template-extends AbstractListEntityType<CalculationState>
 */
class CalculationStateListType extends AbstractListEntityType
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct(CalculationState::class);
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if ($options['use_group_by'] ?? true) {
            $options['group_by'] = function (CalculationState $entity): string {
                $id = $entity->isEditable() ? 'calculationstate.list.editable' : 'calculationstate.list.not_editable';

                return $this->trans($id);
            };
        }
        parent::buildForm($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefined('use_group_by')
            ->setAllowedTypes('use_group_by', 'bool')
            ->setDefaults([
            'choice_label' => 'code',
            'query_builder' => fn (CalculationStateRepository $repository): QueryBuilder => $repository->getQueryBuilderByEditable(),
        ]);
    }
}
