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

namespace App\Form\Parameters;

use App\Enums\MessagePosition;
use App\Form\FormHelper;
use App\Parameter\MessageParameter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractParameterType<MessageParameter>
 */
class MessageParameterType extends AbstractParameterType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    #[\Override]
    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('position')
            ->updateOption('prepend_icon', 'fa-solid fa-crosshairs')
            ->label('parameters.fields.message_position')
            ->addEnumType(MessagePosition::class);

        $helper->field('timeout')
            ->updateOption('prepend_icon', 'fa-solid fa-alarm-clock')
            ->label('parameters.fields.message_timeout')
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getTimeoutChoice());

        $helper->field('progress')
            ->label('parameters.fields.message_progress')
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getProgressChoice());

        $this->addCheckboxType($helper, 'icon', 'parameters.fields.message_icon');
        $this->addCheckboxType($helper, 'title', 'parameters.fields.message_title');
        $this->addCheckboxType($helper, 'subTitle', 'parameters.fields.message_sub_title');
        $this->addCheckboxType($helper, 'close', 'parameters.fields.message_close');
    }

    #[\Override]
    protected function getParameterClass(): string
    {
        return MessageParameter::class;
    }

    /**
     * Gets the message progress height choices.
     *
     * @return array<string, int>
     */
    private function getProgressChoice(): array
    {
        return \array_reduce(
            \range(0, 5),
            fn (array $carry, int $pixel): array => $carry + $this->transCount('counters.pixels', $pixel),
            []
        );
    }

    /**
     * Gets the message timeout choices.
     *
     * @return array<string, int>
     */
    private function getTimeoutChoice(): array
    {
        return \array_reduce(
            \range(1, 5),
            fn (array $carry, int $second): array => $carry + $this->transCount('counters.seconds', $second, 1000),
            []
        );
    }

    /**
     * @return array<string, int>
     */
    private function transCount(string $id, int $count, int $multiplier = 1): array
    {
        return [$this->translator->trans($id, ['%count%' => $count]) => $count * $multiplier];
    }
}
