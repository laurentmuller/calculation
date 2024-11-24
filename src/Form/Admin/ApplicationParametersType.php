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
        if ($this->isSuperAdmin()) {
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
            ->updateOption('prepend_title', 'parameters.fields.customer_phone_title')
            ->notRequired()
            ->addTelType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_FAX)
            ->notRequired()
            ->addFaxType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_EMAIL)
            ->updateOption('prepend_title', 'parameters.fields.customer_email_title')
            ->notRequired()
            ->addEmailType();

        $helper->field(PropertyServiceInterface::P_CUSTOMER_URL)
            ->updateOption('prepend_title', 'parameters.fields.customer_url_title')
            ->notRequired()
            ->addUrlType();
    }

    private function addDefaultProductSection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_PRODUCT_DEFAULT;
        $helper->field($key)
            ->notRequired()
            ->widgetClass('must-validate')
            ->updateOption('placeholder', 'parameters.placeholders.' . $key)
            ->updateAttribute('data-default', '')
            ->add(ProductListType::class);

        $key = PropertyServiceInterface::P_PRODUCT_QUANTITY;
        $helper->field($key)
            ->updateAttribute('data-default', (float) $this->getDefaultValue($key))
            ->addNumberType();

        $key = PropertyServiceInterface::P_PRODUCT_EDIT;
        $this->addCheckBox($helper, $key);
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(PropertyServiceInterface::P_DEFAULT_STATE)
            ->add(CalculationStateListType::class);

        $helper->field(PropertyServiceInterface::P_DEFAULT_CATEGORY)
            ->add(CategoryListType::class);

        $key = PropertyServiceInterface::P_MIN_MARGIN;
        $helper->field($key)
            ->updateAttribute('data-default', (float) $this->getDefaultValue($key) * 100.0)
            ->percent(true)
            ->addPercentType(0);
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        $key = PropertyServiceInterface::P_DISPLAY_CAPTCHA;
        $helper->field($key)
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addTrueFalseType('parameters.display.show', 'parameters.display.hide');

        $key = PropertyServiceInterface::P_STRENGTH_LEVEL;
        $helper->field($key)
            ->label("password.$key")
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->addEnumType(StrengthLevel::class);

        foreach (\array_keys(PropertyServiceInterface::PASSWORD_OPTIONS) as $property) {
            $helper->field($property)
                ->label("password.$property")
                ->updateAttribute('data-default', $this->getDefaultValue($property))
                ->rowClass('mb-1')
                ->addCheckboxType();
        }

        $key = PropertyServiceInterface::P_COMPROMISED_PASSWORD;
        $helper->field($key)
            ->label('password.security_compromised_password')
            ->updateAttribute('data-default', $this->getDefaultValue($key))
            ->rowClass('mb-1')
            ->addCheckboxType();
    }
}
