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

namespace App\Form\Calculation;

use App\Entity\Calculation;
use App\Form\AbstractEntityType;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\FormHelper;
use App\Form\Type\PlainType;
use App\Service\ApplicationService;
use App\Utils\FormatUtils;
use Symfony\Component\Form\Event\PreSetDataEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Edit calculation state type.
 *
 * @template-extends AbstractEntityType<Calculation>
 */
class CalculationEditStateType extends AbstractEntityType
{
    public function __construct(private readonly ApplicationService $service, private readonly TranslatorInterface $translator)
    {
        parent::__construct(Calculation::class);
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('id')
            ->updateOption('number_pattern', PlainType::NUMBER_IDENTIFIER)
            ->widgetClass('text-center')
            ->addPlainType();

        $helper->field('date')
            ->updateOption('time_format', PlainType::FORMAT_NONE)
            ->widgetClass('text-center')
            ->addPlainType();

        $helper->field('overallTotal')
            ->updateOption('number_pattern', PlainType::NUMBER_AMOUNT)
            ->widgetClass('text-end')
            ->addPlainType();

        $helper->field('customer')
            ->addPlainType();

        $helper->field('description')
            ->addPlainType();

        $helper->field('state')
            ->label('calculation.state.new_state')
            ->add(CalculationStateListType::class);

        $helper->listenerPreSetData($this->onPreSetData(...));
    }

    private function getOverallAttributes(Calculation $data): array
    {
        if ($this->isMarginBelow($data)) {
            return [
                'class' => 'text-end',
                'data-bs-html' => 'true',
                'data-bs-toggle' => 'tooltip',
                'data-bs-custom-class' => 'tooltip-danger',
                'data-bs-title' => $this->translateMarginBelow($data),
            ];
        }

        return ['class' => 'text-end'];
    }

    private function getOverallTextClass(Calculation $data): ?string
    {
        return $this->isMarginBelow($data) ? 'text-danger' : null;
    }

    private function isMarginBelow(Calculation $data): bool
    {
        return $this->service->isMarginBelow($data);
    }

    private function onPreSetData(PreSetDataEvent $event): void
    {
        /** @phpstan-var Calculation $data */
        $data = $event->getData();
        $options = [
            'expanded' => true,
            'percent_decimals' => 0,
            'label' => 'calculation.fields.margin',
            'number_pattern' => PlainType::NUMBER_PERCENT,
            'attr' => $this->getOverallAttributes($data),
            'text_class' => $this->getOverallTextClass($data),
        ];
        $event->getForm()->add('overallMargin', PlainType::class, $options);
    }

    private function translateMarginBelow(Calculation $data): string
    {
        $minimum = $this->service->getMinMargin();
        $margin = $data->getOverallMargin();

        return $this->translator
            ->trans('calculation.list.margin_below', [
                '%margin%' => FormatUtils::formatPercent($margin),
                '%minimum%' => FormatUtils::formatPercent($minimum),
            ]);
    }
}
