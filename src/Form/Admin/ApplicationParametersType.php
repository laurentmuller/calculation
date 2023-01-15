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
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type for application parameters.
 */
class ApplicationParametersType extends AbstractParametersType
{
    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator, ApplicationService $service)
    {
        parent::__construct($security, $translator, $service->getDefaultValues());
    }

    protected function addSections(FormHelper $helper): void
    {
        $this->addCustomerSection($helper);
        $this->addDefaultValueSection($helper);
        $this->addDefaultProductSection($helper);
        $this->addDisplaySection($helper);
        $this->addMessageSection($helper);
        $this->addHomePageSection($helper);
        $this->addOptionsSection($helper);
        if ($this->superAdmin) {
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
        $key = PropertyServiceInterface::P_DEFAULT_PRODUCT;
        $helper->field($key)
            ->notRequired()
            ->updateOption('placeholder', 'parameters.placeholders.' . $key)
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $key = PropertyServiceInterface::P_DEFAULT_PRODUCT_QUANTITY;
        $helper->field($key)
            ->updateAttribute('data-default', (float) $this->getDefaultValue($key))
            ->addNumberType();

        $key = PropertyServiceInterface::P_DEFAULT_PRODUCT_EDIT;
        $this->addCheckBox($helper, $key, '');
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DEFAULT_STATE)
            ->add(CalculationStateListType::class);

        $helper->field(PropertyServiceInterface::P_DEFAULT_CATEGORY)
            ->add(CategoryListType::class);

        $key = PropertyServiceInterface::P_MIN_MARGIN;
        $helper->field($key)
            ->updateAttribute('data-default', (float) $this->getDefaultValue($key) * 100)
            ->percent(true)
            ->addPercentType(0);
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_DISPLAY_CAPTCHA;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);

        $key = PropertyServiceInterface::P_STRENGTH_LEVEL;
        $helper->field($key)
            ->label('password.strength_level')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addEnumType(StrengthLevel::class);

        foreach (PropertyServiceInterface::PASSWORD_OPTIONS as $option) {
            $helper->field($option)
                ->label("password.$option")
                ->updateAttribute('data-default', $this->getDefaultValue($option))
                ->rowClass('mb-1')
                ->notRequired()
                ->addCheckboxType();
        }
    }
}
