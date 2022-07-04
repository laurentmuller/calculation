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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Abstract parameters type.
 */
abstract class AbstractParametersType extends AbstractType
{
    protected bool $superAdmin = false;

    /**
     * Constructor.
     */
    public function __construct(Security $security, private readonly array $defaultValues)
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

    protected function addDisplaySection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DISPLAY_MODE)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_DISPLAY_MODE))
            ->addEnumType(TableView::class);

        $helper->field(PropertyServiceInterface::P_EDIT_ACTION)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_EDIT_ACTION))
            ->addEnumType(EntityAction::class);
    }

    protected function addHomePageSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_PANEL_STATE)
            ->label('index.panel_state')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_PANEL_STATE))
            ->help('parameters.helps.' . PropertyServiceInterface::P_PANEL_STATE)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(PropertyServiceInterface::P_PANEL_MONTH)
            ->label('index.panel_month')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_PANEL_MONTH))
            ->help('parameters.helps.' . PropertyServiceInterface::P_PANEL_MONTH)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(PropertyServiceInterface::P_PANEL_CATALOG)
            ->label('index.panel_count')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_PANEL_CATALOG))
            ->help('parameters.helps.' . PropertyServiceInterface::P_PANEL_CATALOG)
            ->rowClass('mb-1')
            ->notRequired()
            ->addCheckboxType();

        /** @psalm-var int $calculations */
        $calculations = $this->getDefaultValue(PropertyServiceInterface::P_PANEL_CALCULATION);
        $helper->field(PropertyServiceInterface::P_PANEL_CALCULATION)
            ->help('parameters.helps.' . PropertyServiceInterface::P_PANEL_CALCULATION)
            ->updateRowAttribute('data-default', $calculations)
            ->labelClass('radio-inline')
            ->updateOptions([
                'choice_translation_domain' => false,
                'expanded' => true,
            ])
            ->addChoiceType($this->getCalculationChoices());
    }

    protected function addMessageSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_MESSAGE_POSITION)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_POSITION))
            ->addEnumType(MessagePosition::class);
        $helper->field(PropertyServiceInterface::P_MESSAGE_TIMEOUT)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_TIMEOUT))
            ->addChoiceType($this->getTimeouts());
        $helper->field(PropertyServiceInterface::P_MESSAGE_TITLE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_TITLE))
            ->notRequired()
            ->addCheckboxType();
        $helper->field(PropertyServiceInterface::P_MESSAGE_SUB_TITLE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_SUB_TITLE))
            ->notRequired()
            ->addCheckboxType();
        $helper->field(PropertyServiceInterface::P_MESSAGE_PROGRESS)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_PROGRESS))
            ->notRequired()
            ->addCheckboxType();
        $helper->field(PropertyServiceInterface::P_MESSAGE_ICON)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_ICON))
            ->notRequired()
            ->addCheckboxType();
        $helper->field(PropertyServiceInterface::P_MESSAGE_CLOSE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_MESSAGE_CLOSE))
            ->notRequired()
            ->addCheckboxType();
    }

    protected function addOptionsSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_QR_CODE)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_QR_CODE))
            ->help('parameters.helps.' . PropertyServiceInterface::P_QR_CODE)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(PropertyServiceInterface::P_PRINT_ADDRESS)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_PRINT_ADDRESS))
            ->help('parameters.helps.' . PropertyServiceInterface::P_PRINT_ADDRESS)
            ->notRequired()
            ->addCheckboxType();
    }

    /**
     * Add sections.
     */
    abstract protected function addSections(FormHelper $helper): void;

    /**
     * Gets the default value for the given name.
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
     * Gets the displayed calculations choices.
     */
    private function getCalculationChoices(): array
    {
        $values = [5, 10, 15, 20, 25];

        return \array_combine($values, $values);
    }

    /**
     * Gets the message timeout choices.
     */
    private function getTimeouts(): array
    {
        $result = [];
        for ($i = 1; $i < 6; ++$i) {
            $result["parameters.message_timeout.$i"] = $i * 1000;
        }

        return $result;
    }
}
