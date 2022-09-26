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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract parameters type.
 */
abstract class AbstractParametersType extends AbstractType
{
    use TranslatorTrait;

    private bool $superAdmin = false;

    /**
     * Constructor.
     *
     * @param array<string, mixed> $defaultValues
     */
    public function __construct(Security $security, private readonly TranslatorInterface $translator, private readonly array $defaultValues)
    {
        if (null !== ($user = $security->getUser())) {
            $this->superAdmin = $user instanceof RoleInterface && $user->isSuperAdmin();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        $helper = new FormHelper($builder, 'parameters.fields.');
        $this->addSections($helper);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return '';
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
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
        $key = PropertyServiceInterface::P_PANEL_STATE;
        $helper->field($key)
            ->label('index.panel_state')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->notRequired()
            ->addCheckboxType();

        $key = PropertyServiceInterface::P_PANEL_MONTH;
        $helper->field($key)
            ->label('index.panel_month')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->notRequired()
            ->addCheckboxType();

        $key = PropertyServiceInterface::P_PANEL_CATALOG;
        $helper->field($key)
            ->label('index.panel_count')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->rowClass('mb-1')
            ->notRequired()
            ->addCheckboxType();

        $key = PropertyServiceInterface::P_PANEL_CALCULATION;
        $helper->field($key)
            ->help('parameters.helps.' . $key)
            ->updateRowAttribute('data-default', $this->getDefaultValue($key))
            ->labelClass('radio-inline')
            ->updateOptions([
                'choice_translation_domain' => false,
                'expanded' => true,
            ])
            ->addChoiceType($this->getCalculationChoices());

        $key = PropertyServiceInterface::P_STATUS_BAR;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->rowClass('mb-1')
            ->notRequired()
            ->addCheckboxType();
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
            ->addChoiceType($this->getTimeouts());

        $key = PropertyServiceInterface::P_MESSAGE_PROGRESS;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->updateOption('choice_translation_domain', false)
            ->addChoiceType($this->getProgress());

        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_TITLE);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_SUB_TITLE);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_ICON);
        $this->addCheckBox($helper, PropertyServiceInterface::P_MESSAGE_CLOSE);
    }

    protected function addOptionsSection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_QR_CODE;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->notRequired()
            ->addCheckboxType();

        $key = PropertyServiceInterface::P_PRINT_ADDRESS;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->help('parameters.helps.' . $key)
            ->notRequired()
            ->addCheckboxType();
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
        /** @psalm-var mixed $value */
        $value = $this->defaultValues[$name] ?? $default;
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }
        if (\is_bool($value)) {
            return \json_encode($value);
        }

        return $value;
    }

    /**
     * Returns if the current logged user has the super administrator role.
     */
    protected function isSuperAdmin(): bool
    {
        return $this->superAdmin;
    }

    /**
     * Adds a checkbox type for message option.
     */
    private function addCheckBox(FormHelper $helper, string $key): void
    {
        $helper->field($key)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * Gets the displayed calculations choices.
     */
    private function getCalculationChoices(): array
    {
        $values = [5, 10, 15, 20, 25];

        return \array_combine($values, $values);
    }

    /**
     * Gets the message progress height choices.
     */
    private function getProgress(): array
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
    private function getTimeouts(): array
    {
        $result = [];
        foreach (\range(1, 5) as $second) {
            $key = $this->trans('counters.seconds', ['%count%' => $second]);
            $result[$key] = $second * 1000;
        }

        return $result;
    }
}
