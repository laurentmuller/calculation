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

namespace App\Form\Admin;

use App\Enums\EntityAction;
use App\Enums\TableView;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Form\Product\ProductListType;
use App\Form\Type\MinStrengthType;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use App\Util\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;

/**
 * Type for application parameters.
 *
 * @author Laurent Muller
 */
class ParametersType extends AbstractType implements ApplicationServiceInterface
{
    /**
     * The password options.
     */
    final public const PASSWORD_OPTIONS = [
        'letters',
        'numbers',
        'specialchar',
        'casediff',
        'email',
        'pwned',
    ];

    private bool $superAdmin = false;

    /**
     * Constructor.
     */
    public function __construct(Security $security, private readonly ApplicationService $application)
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
        $this->addCustomerSection($helper);
        $this->addDefaultValueSection($helper);
        $this->addDefaultProductSection($helper);
        $this->addDisplaySection($helper);
        $this->addFlashbagSection($helper);

        if ($this->superAdmin) {
            $this->addHomePageSection($helper);
            $this->addOptionsSection($helper);
            $this->addSecuritySection($helper);
        }
    }

    private function addCustomerSection(FormHelper $helper): void
    {
        $helper->field(self::P_CUSTOMER_NAME)
            ->updateAttribute('spellcheck', 'false')
            ->addTextType();

        $helper->field(self::P_CUSTOMER_ADDRESS)
            ->notRequired()
            ->addTextType();

        $helper->field(self::P_CUSTOMER_ZIP_CITY)
            ->notRequired()
            ->addTextType();

        $helper->field(self::P_CUSTOMER_PHONE)
            ->notRequired()
            ->addTelType();

        $helper->field(self::P_CUSTOMER_FAX)
            ->notRequired()
            ->addFaxType();

        $helper->field(self::P_CUSTOMER_EMAIL)
            ->notRequired()
            ->addEmailType();

        $helper->field(self::P_CUSTOMER_URL)
            ->notRequired()
            ->addUrlType();
    }

    private function addDefaultProductSection(FormHelper $helper): void
    {
        $helper->field(self::P_DEFAULT_PRODUCT)
            ->notRequired()
            ->updateOption('placeholder', 'parameters.placehoders.' . self::P_DEFAULT_PRODUCT)
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $helper->field(self::P_DEFAULT_PRODUCT_QUANTITY)
            ->updateAttribute('data-default', FormatUtils::formatAmount(0))
            ->addNumberType();

        $helper->field(self::P_DEFAULT_PRODUCT_EDIT)
            ->updateAttribute('data-default', \json_encode(false))
            ->notRequired()
            ->addCheckboxType();
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(self::P_DEFAULT_STATE)
            ->add(CalculationStateListType::class);

        $helper->field(self::P_DEFAULT_CATEGORY)
            ->add(CategoryListType::class);

        $helper->field(self::P_MIN_MARGIN)
            ->updateAttribute('data-default', self::DEFAULT_MIN_MARGIN * 100)
            ->percent(true)
            ->addPercentType(0);
    }

    private function addDisplaySection(FormHelper $helper): void
    {
        $helper->field(self::P_DISPLAY_MODE)
            ->updateAttribute('data-default', self::DEFAULT_DISPLAY_MODE->value)
            ->updateOption('choice_label', static fn (TableView $choice): string => 'parameters.tabular.' . $choice->value)
            ->addEnumType(TableView::class);

        $helper->field(self::P_EDIT_ACTION)
            ->updateAttribute('data-default', self::DEFAULT_ACTION->value)
            ->updateOption('choice_label', static fn (EntityAction $choice): string => 'parameters.editAction.' . $choice->value)
            ->addEnumType(EntityAction::class);
    }

    private function addFlashbagSection(FormHelper $helper): void
    {
        $helper->field(self::P_MESSAGE_POSITION)
            ->updateAttribute('data-default', self::DEFAULT_MESSAGE_POSITION)
            ->addChoiceType($this->getPositions());
        $helper->field(self::P_MESSAGE_TIMEOUT)
            ->updateAttribute('data-default', self::DEFAULT_MESSAGE_TIMEOUT)
            ->addChoiceType($this->getTimeouts());

        $helper->field(self::P_MESSAGE_TITLE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', (int) self::DEFAULT_MESSAGE_TITLE)
            ->notRequired()
            ->addCheckboxType();
        $helper->field(self::P_MESSAGE_SUB_TITLE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', (int) self::DEFAULT_MESSAGE_SUB_TITLE)
            ->notRequired()
            ->addCheckboxType();
        $helper->field(self::P_MESSAGE_PROGRESS)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', (int) self::DEFAULT_PROGRESS)
            ->notRequired()
            ->addCheckboxType();
        $helper->field(self::P_MESSAGE_ICON)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', (int) self::DEFAULT_MESSAGE_ICON)
            ->notRequired()
            ->addCheckboxType();
        $helper->field(self::P_MESSAGE_CLOSE)
            ->rowClass('custom-control-inline')
            ->updateAttribute('data-default', (int) self::DEFAULT_MESSAGE_CLOSE)
            ->notRequired()
            ->addCheckboxType();
    }

    private function addHomePageSection(FormHelper $helper): void
    {
        $helper->field(self::P_PANEL_STATE)
            ->label('index.panel_state')
            ->updateAttribute('data-default', 1)
            ->help('parameters.helps.' . self::P_PANEL_STATE)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_PANEL_MONTH)
            ->label('index.panel_month')
            ->updateAttribute('data-default', 1)
            ->help('parameters.helps.' . self::P_PANEL_MONTH)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_PANEL_CATALOG)
            ->label('index.panel_count')
            ->updateAttribute('data-default', 1)
            ->help('parameters.helps.' . self::P_PANEL_CATALOG)
            ->rowClass('mb-1')
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_PANEL_CALCULATION)
            ->updateAttribute('data-default', self::DEFAULT_PANEL_CALCULATION)
            ->help('parameters.helps.' . self::P_PANEL_CALCULATION)
            ->labelClass('radio-inline')
            ->updateOptions([
                'choice_translation_domain' => false,
                'expanded' => true,
            ])
            ->addChoiceType($this->getCalculationChoices());
    }

    private function addOptionsSection(FormHelper $helper): void
    {
        $helper->field(self::P_QR_CODE)
            ->updateAttribute('data-default', (int) self::DEFAULT_QR_CODE)
            ->help('parameters.helps.' . self::P_QR_CODE)
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_PRINT_ADDRESS)
            ->updateAttribute('data-default', (int) self::DEFAULT_PRINT_ADDRESS)
            ->help('parameters.helps.' . self::P_PRINT_ADDRESS)
            ->notRequired()
            ->addCheckboxType();
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        // security
        $captcha = (int) !$this->application->getDebug();
        $helper->field(self::P_DISPLAY_CAPTCHA)
            ->updateAttribute('data-default', $captcha)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);

        $helper->field(self::P_MIN_STRENGTH)
            ->label('password.minstrength')
            ->updateAttribute('data-default', -1)
            ->add(MinStrengthType::class);

        // password options
        foreach (self::PASSWORD_OPTIONS as $option) {
            $helper->field($option)
                ->label("password.$option")
                ->updateAttribute('data-default', 0)
                ->rowClass('mb-1')
                ->notRequired()
                ->addCheckboxType();
        }
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
     * Gets the message position choices.
     */
    private function getPositions(): array
    {
        $entries = [
            'top-left',
            'top-center',
            'top-right',

            'center-left',
            'center-center',
            'center-right',

            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];

        $result = [];
        foreach ($entries as $entry) {
            $result['parameters.message_position.' . $entry] = $entry;
        }

        return $result;
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
