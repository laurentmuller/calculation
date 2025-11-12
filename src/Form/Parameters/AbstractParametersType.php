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

use App\Enums\EntityAction;
use App\Enums\MessagePosition;
use App\Enums\TableView;
use App\Form\FormHelper;
use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Traits\TranslatorTrait;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract parameters type.
 *
 * @extends AbstractType<FormTypeInterface>
 */
abstract class AbstractParametersType extends AbstractType
{
    use TranslatorTrait;

    /**
     * The displayed calculations range.
     */
    final public const CALCULATIONS_RANGE = [4, 8, 12, 16, 20];

    private const LABEL_PREFIX = 'parameters.fields.';

    /**
     * @param array<string, mixed> $defaultValues
     */
    public function __construct(
        private readonly Security $security,
        private readonly TranslatorInterface $translator,
        private readonly array $defaultValues
    ) {
    }

    /**
     * @phpstan-param FormBuilderInterface<mixed> $builder
     */
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $helper = new FormHelper($builder, self::LABEL_PREFIX);
        $this->addSections($helper);
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return '';
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Adds a checkbox type.
     */
    protected function addCheckBox(FormHelper $helper, string $key): void
    {
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addCheckboxType(inline: true);
    }

    protected function addDisplaySection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_DISPLAY_MODE;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addEnumType(TableView::class);

        $key = PropertyServiceInterface::P_EDIT_ACTION;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addEnumType(EntityAction::class);
    }

    protected function addHomePageSection(FormHelper $helper): void
    {
        $this->addOption($helper, PropertyServiceInterface::P_PANEL_STATE, 'index.panel_state');
        $this->addOption($helper, PropertyServiceInterface::P_PANEL_MONTH, 'index.panel_month');

        $key = PropertyServiceInterface::P_PANEL_CATALOG;
        $helper->field($key)
            ->label('index.panel_catalog')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->rowClass('mb-1')
            ->addCheckboxType();

        $key = PropertyServiceInterface::P_CALCULATIONS;
        $helper->field($key)
            ->updateRowAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->labelClass('radio-inline')
            ->updateOptions([
                'choice_translation_domain' => false,
                'expanded' => true,
            ])
            ->addChoiceType($this->getCalculationsChoice());

        $this->addOption($helper, PropertyServiceInterface::P_STATUS_BAR);
        $this->addOption($helper, PropertyServiceInterface::P_DARK_NAVIGATION);
    }

    protected function addMessageSection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_MESSAGE_POSITION;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addEnumType(MessagePosition::class);

        $key = PropertyServiceInterface::P_MESSAGE_TIMEOUT;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getTimeoutChoice());

        $key = PropertyServiceInterface::P_MESSAGE_PROGRESS;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getProgressChoice());

        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_TITLE);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_SUB_TITLE);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_ICON);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_CLOSE);
    }

    protected function addOptionsSection(FormHelper $helper): void
    {
        $this->addOption($helper, PropertyServiceInterface::P_QR_CODE);
        $this->addOption($helper, PropertyServiceInterface::P_PRINT_ADDRESS);
    }

    /**
     * Add sections.
     */
    abstract protected function addSections(FormHelper $helper): void;

    /**
     * Gets the default value for the given property name.
     */
    protected function getDefaultValue(string $name, mixed $default = ''): mixed
    {
        /** @phpstan-var mixed $value */
        $value = $this->defaultValues[$name] ?? $default;
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if (\is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }

    protected function isSuperAdmin(): bool
    {
        return $this->security->isGranted(RoleInterface::ROLE_SUPER_ADMIN);
    }

    private function addOption(FormHelper $helper, string $key, ?string $label = null): void
    {
        $label ??= self::LABEL_PREFIX . $key;
        $helper->field($key)
            ->label($label)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->addCheckboxType();
    }

    /**
     * Gets the displayed calculations choice.
     */
    private function getCalculationsChoice(): array
    {
        return \array_combine(self::CALCULATIONS_RANGE, self::CALCULATIONS_RANGE);
    }

    /**
     * Gets the message progress height choices.
     */
    private function getProgressChoice(): array
    {
        $result = [];
        foreach (\range(0, 5) as $pixel) {
            $key = $this->trans('counters.pixels', ['%count%' => $pixel]);
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
            $key = $this->trans('counters.seconds', ['%count%' => $second]);
            $result[$key] = $second * 1000;
        }

        return $result;
    }
}
