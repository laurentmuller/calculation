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

class MessageParameterType extends AbstractParameterType
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    protected function addFormFields(FormHelper $helper): void
    {
        $helper->field('position')
            ->label('parameters.fields.message_position')
            ->addEnumType(MessagePosition::class);

        $helper->field('timeout')
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

    protected function getParameterClass(): string
    {
        return MessageParameter::class;
    }

    /**
     * Gets the message progress height choices.
     */
    private function getProgressChoice(): array
    {
        $result = [];
        foreach (\range(0, 5) as $pixel) {
            $key = $this->translator->trans('counters.pixels', ['%count%' => $pixel]);
            $result[$key] = $pixel;
        }

        return $result;
    }

    /**
     * Gets the message timeout choices.
     */
    private function getTimeoutChoice(): array
    {
        $result = [];
        foreach (\range(1, 5) as $second) {
            $key = $this->translator->trans('counters.seconds', ['%count%' => $second]);
            $result[$key] = $second * 1000;
        }

        return $result;
    }
}
