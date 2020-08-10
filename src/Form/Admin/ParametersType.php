<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Form\Admin;

use App\Form\FormHelper;
use App\Interfaces\ApplicationServiceInterface;
use App\Interfaces\RoleInterface;
use App\Service\ApplicationService;
use App\Traits\FormatterTrait;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type for application parameters.
 *
 * @author Laurent Muller
 */
class ParametersType extends AbstractType implements ApplicationServiceInterface
{
    use FormatterTrait;
    use TranslatorTrait;

    /**
     * The password options.
     */
    public const PASSWORD_OPTIONS = [
        'password_letters',
        'password_numbers',
        'password_special_character',
        'password_case_diff',
        'password_email',
        'password_black_list',
        'password_pwned',
    ];

    /**
     * @var bool
     */
    private $superAdmin = false;

    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator, ApplicationService $application)
    {
        $this->translator = $translator;
        $this->application = $application;
        if ($user = $security->getUser()) {
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

        // super admin fields
        $this->addAdminSection($helper);

        // security
        $this->addSecuritySection($helper);
    }

    private function addAdminSection(FormHelper $helper): void
    {
        if ($this->superAdmin) {
            // display
            $helper->field(self::DATE_FORMAT)
                ->updateOption('format', 'date')
                ->updateAttribute('data-default', FormatUtils::getDateType())
                ->add(DateTimeFormatType::class);

            $helper->field(self::TIME_FORMAT)
                ->updateOption('format', 'time')
                ->updateAttribute('data-default', FormatUtils::getTimeType())
                ->add(DateTimeFormatType::class);

            $helper->field(self::GROUPING_SEPARATOR)
                ->updateAttribute('data-default', FormatUtils::getGrouping())
                ->add(GroupingSeparatorType::class);

            $helper->field(self::DECIMAL_SEPARATOR)
                ->updateAttribute('data-default', FormatUtils::getDecimal())
                ->add(DecimalSeparatorType::class);

            // security
            $this->addSecuritySection($helper);
        }
    }

    private function addCustomerSection(FormHelper $helper): void
    {
        $helper->field(self::CUSTOMER_NAME)
            ->updateRowAttribute('class', 'ml-2')
            ->addTextType();

        $helper->field(self::CUSTOMER_URL)
            ->updateRowAttribute('class', 'ml-2')
            ->addUrlType();
    }

    private function addDefaultValueSection(FormHelper $helper): void
    {
        $helper->field(self::DEFAULT_STATE)
            ->addStateType();

        $helper->field(self::EDIT_ACTION)
            ->updateAttribute('data-default', (int) self::DEFAULT_EDIT_ACTION)
            ->addChoiceType([
                'parameters.editAction.show' => false,
                'parameters.editAction.edit' => true,
            ]);

        $helper->field(self::MIN_MARGIN)
            ->updateAttribute('data-default', self::DEFAULT_MIN_MARGIN * 100)
            ->percent(true)
            ->addPercentType(0);

        $helper->field(self::DISPLAY_TABULAR)
            ->updateAttribute('data-default', (int) self::DEFAULT_TABULAR)
            ->addChoiceType([
                'parameters.tabular.table' => true,
                'parameters.tabular.flex' => false,
            ]);
    }

    private function addFlashbagSection(FormHelper $helper): void
    {
        $helper->field(self::MESSAGE_POSITION)
            ->updateAttribute('data-default', self::DEFAULT_POSITION)
            ->addChoiceType($this->getPositions());

        $helper->field(self::MESSAGE_TIMEOUT)
            ->updateAttribute('data-default', self::DEFAULT_TIMEOUT)
            ->addChoiceType($this->getTimeouts());

        $helper->field(self::MESSAGE_SUB_TITLE)
            ->updateAttribute('data-default', (int) self::DEFAULT_SUB_TITLE)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);
    }

    private function addSecuritySection(FormHelper $helper): void
    {
        // security
        $captcha = (int) !$this->application->isDebug();
        $helper->field(self::DISPLAY_CAPTCHA)
            ->updateAttribute('data-default', $captcha)
            ->addChoiceType([
                'parameters.display.show' => true,
                'parameters.display.hide' => false,
            ]);

        $helper->field(self::MIN_STRENGTH)
            ->updateAttribute('data-default', -1)
            ->addChoiceType([
                'password.strength_level.none' => -1,
                'password.strength_level.very_weak' => 0,
                'password.strength_level.weak' => 1,
                'password.strength_level.medium' => 2,
                'password.strength_level.very_strong' => 3,
            ]);

        // password options
        foreach (self::PASSWORD_OPTIONS as $option) {
            $helper->field($option)
                ->label("parameters.password.{$option}")
                ->updateAttribute('data-default', 0)
                ->updateRowAttribute('class', 'mb-1 ml-1')
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
            'bottom-left',
            'bottom-center',
            'bottom-right',
        ];

        $result = [];
        foreach ($entries as $entry) {
            $key = 'parameters.messagePosition.' . \str_replace('-', '_', $entry);
            $result[$key] = $entry;
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
            $result["parameters.messageTimeout.{$i}"] = $i * 1000;
        }

        return  $result;
    }
}
