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

use App\Form\FormHelper;
use App\Form\Product\ProductListType;
use App\Form\Type\MinStrengthType;
use App\Interfaces\ActionInterface;
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
    public const PASSWORD_OPTIONS = [
        'letters',
        'numbers',
        'specialchar',
        'casediff',
        'email',
        'pwned',
    ];

    private ApplicationService $application;

    private bool $superAdmin = false;

    /**
     * Constructor.
     */
    public function __construct(Security $security, ApplicationService $application)
    {
        $this->application = $application;
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

        // customer
        $this->addCustomerSection($helper);

        // default values
        $this->addDefaultValueSection($helper);

        // flashbag
        $this->addFlashbagSection($helper);

        // options
        if ($this->superAdmin) {
            $this->addOptionsSection($helper);
        }

        // security
        if ($this->superAdmin) {
            $this->addSecuritySection($helper);
        }
    }

    private function addCustomerSection(FormHelper $helper): void
    {
        $helper->field(self::P_CUSTOMER_NAME)
            ->updateAttribute('spellcheck', 'false')
            ->rowClass('ml-1 mt-1')
            ->addTextType();

        $helper->field(self::P_CUSTOMER_ADDRESS)
            ->rowClass('ml-1 mt-1')
            ->notRequired()
            ->addTextType();

        $helper->field(self::P_CUSTOMER_ZIP_CITY)
            ->rowClass('ml-1 mt-1')
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

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(self::P_DEFAULT_STATE)
            ->addCalculationStateListType();

        $helper->field(self::P_DEFAULT_CATEGORY)
            ->addCategoryListType();

        $helper->field(self::P_MIN_MARGIN)
            ->updateAttribute('data-default', self::DEFAULT_MIN_MARGIN * 100)
            ->percent(true)
            ->addPercentType(0);

        $helper->field(self::P_DEFAULT_PRODUCT)
            ->notRequired()
            ->updateOption('placeholder', 'parameters.placehoders.' . self::P_DEFAULT_PRODUCT)
            ->help('parameters.helps.' . self::P_DEFAULT_PRODUCT)
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $helper->field(self::P_DEFAULT_PRODUCT_QUANTITY)
            ->updateAttribute('data-default', FormatUtils::formatAmount(0))
            ->addNumberType();

        $helper->field(self::P_DEFAULT_PRODUCT_EDIT)
            ->updateAttribute('data-default', \json_encode(true))
            ->rowClass('ml-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_DISPLAY_TABULAR)
            ->updateAttribute('data-default', (int) self::DEFAULT_TABULAR)
            ->addChoiceType([
                'parameters.tabular.table' => true,
                'parameters.tabular.flex' => false,
            ]);

        $helper->field(self::P_EDIT_ACTION)
            ->updateAttribute('data-default', self::DEFAULT_ACTION)
            ->addChoiceType([
                'parameters.editAction.show' => ActionInterface::ACTION_SHOW,
                'parameters.editAction.edit' => ActionInterface::ACTION_EDIT,
                'parameters.editAction.none' => ActionInterface::ACTION_NONE,
            ]);
    }

    private function addFlashbagSection(FormHelper $helper): void
    {
        $helper->field(self::P_MESSAGE_POSITION)
            ->updateAttribute('data-default', self::DEFAULT_POSITION)
            ->addChoiceType($this->getPositions());

        $helper->field(self::P_MESSAGE_TIMEOUT)
            ->updateAttribute('data-default', self::DEFAULT_TIMEOUT)
            ->addChoiceType($this->getTimeouts());

        $helper->field(self::P_MESSAGE_SUB_TITLE)
            ->updateAttribute('data-default', (int) self::DEFAULT_SUB_TITLE)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);
    }

    private function addOptionsSection(FormHelper $helper): void
    {
        $helper->field(self::P_QR_CODE)
            ->updateAttribute('data-default', (int) self::DEFAULT_QR_CODE)
            ->help('parameters.helps.' . self::P_QR_CODE)
            ->rowClass('ml-3 mt-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field(self::P_PRINT_ADDRESS)
            ->updateAttribute('data-default', (int) self::DEFAULT_PRINT_ADDRESS)
            ->help('parameters.helps.' . self::P_PRINT_ADDRESS)
            ->rowClass('ml-3 mt-2')
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
                ->label("password.{$option}")
                ->updateAttribute('data-default', 0)
                ->rowClass('mb-1 ml-1')
                ->notRequired()
                ->addCheckboxType();
        }
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
            $result["parameters.message_timeout.{$i}"] = $i * 1000;
        }

        return $result;
    }
}
