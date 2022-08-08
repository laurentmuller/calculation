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

namespace App\Form\Admin;

use App\Enums\StrengthLevel;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Form\Parameters\AbstractParametersType;
use App\Form\Product\ProductListType;
use App\Interfaces\PropertyServiceInterface;
use App\Service\ApplicationService;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type for application parameters.
 */
class ApplicationParametersType extends AbstractParametersType
{
    /**
     * The password options.
     */
    final public const PASSWORD_OPTIONS = [
        'letters',
        'numbers',
        'special_char',
        'case_diff',
        'email',
        'pwned',
    ];

    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator, ApplicationService $application)
    {
        $defaultValues = $application->getDefaultValues();
        foreach (self::PASSWORD_OPTIONS as $option) {
            $defaultValues[$option] = false;
        }
        parent::__construct($security, $translator, $defaultValues);
    }

    protected function addSections(FormHelper $helper): void
    {
        $this->addCustomerSection($helper);
        $this->addDefaultValueSection($helper);
        $this->addDefaultProductSection($helper);
        $this->addDisplaySection($helper);
        $this->addMessageSection($helper);
        if ($this->isSuperAdmin()) {
            $this->addHomePageSection($helper);
            $this->addOptionsSection($helper);
            $this->addSecuritySection($helper);
        }
    }

    private function addCustomerSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_CUSTOMER_NAME)
            ->updateAttribute('spellcheck', 'false')
            ->addTextType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_ADDRESS)
            ->notRequired()
            ->addTextType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_ZIP_CITY)
            ->notRequired()
            ->addTextType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_PHONE)
            ->notRequired()
            ->addTelType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_FAX)
            ->notRequired()
            ->addFaxType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_EMAIL)
            ->notRequired()
            ->addEmailType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_URL)
            ->notRequired()
            ->addUrlType();
    }

    private function addDefaultProductSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DEFAULT_PRODUCT)
            ->notRequired()
            ->updateOption('placeholder', 'parameters.placeholders.' . PropertyServiceInterface::P_DEFAULT_PRODUCT)
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $helper->field(PropertyServiceInterface::P_DEFAULT_PRODUCT_QUANTITY)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_DEFAULT_PRODUCT_QUANTITY))
            ->addNumberType();

        $helper->field(PropertyServiceInterface::P_DEFAULT_PRODUCT_EDIT)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_DEFAULT_PRODUCT_EDIT))
            ->notRequired()
            ->addCheckboxType();
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DEFAULT_STATE)
            ->add(CalculationStateListType::class);

        $helper->field(PropertyServiceInterface::P_DEFAULT_CATEGORY)
            ->add(CategoryListType::class);

        $helper->field(PropertyServiceInterface::P_MIN_MARGIN)
            ->updateAttribute('data-default', (float) $this->getDefaultValue(PropertyServiceInterface::P_MIN_MARGIN) * 100)
            ->percent(true)
            ->addPercentType(0);
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DISPLAY_CAPTCHA)
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_DISPLAY_CAPTCHA))
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);

        $helper->field(PropertyServiceInterface::P_STRENGTH_LEVEL)
            ->label('password.strength_level')
            ->updateAttribute('data-default', $this->getDefaultValue(PropertyServiceInterface::P_STRENGTH_LEVEL))
            ->addEnumType(StrengthLevel::class);

        foreach (self::PASSWORD_OPTIONS as $option) {
            $helper->field($option)
                ->label("password.$option")
                ->updateAttribute('data-default', $this->getDefaultValue($option))
                ->rowClass('mb-1')
                ->notRequired()
                ->addCheckboxType();
        }
    }
}
