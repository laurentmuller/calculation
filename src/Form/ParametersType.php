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

namespace App\Form;

use App\Form\Type\DateTimeFormatType;
use App\Form\Type\DecimalSeparatorType;
use App\Form\Type\GroupingSeparatorType;
use App\Interfaces\IApplicationService;
use App\Interfaces\IRole;
use App\Service\ApplicationService;
use App\Traits\FormatterTrait;
use App\Traits\TranslatorTrait;
use App\Utils\FormatUtils;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type for application parameters.
 *
 * @author Laurent Muller
 */
class ParametersType extends AbstractType implements IApplicationService
{
    use FormatterTrait;
    use TranslatorTrait;

    /**
     * @var Security
     */
    private $security;

    /**
     * Constructor.
     *
     * @param Security            $security    the ssecurity service
     * @param TranslatorInterface $translator  the translator service
     * @param ApplicationService  $application the application service
     */
    public function __construct(Security $security, TranslatorInterface $translator, ApplicationService $application)
    {
        $this->security = $security;
        $this->translator = $translator;
        $this->application = $application;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $helper = new FormHelper($builder);

        $helper->field(self::CUSTOMER_NAME)
            ->label('parameters.fields.customerName')
            ->addTextType();

        $helper->field(self::CUSTOMER_URL)
            ->label('parameters.fields.customerUrl')
            ->addUrlType();

        $helper->field(self::DEFAULT_STATE)
            ->label('parameters.fields.defaultState')
            ->addStateType();

        $helper->field(self::EDIT_ACTION)
            ->label('parameters.fields.editAction')
            ->updateAttribute('data-default', (int) self::DEFAULT_EDIT_ACTION)
            ->addChoiceType([
                'parameters.editAction.show' => false,
                'parameters.editAction.edit' => true,
            ]);

        $helper->field(self::MESSAGE_POSITION)
            ->label('parameters.fields.messagePosition')
            ->updateAttribute('data-default', self::DEFAULT_POSITION)
            ->addChoiceType($this->getPositions());

        $helper->field(self::MESSAGE_TIMEOUT)
            ->label('parameters.fields.messageTimeout')
            ->updateAttribute('data-default', self::DEFAULT_TIMEOUT)
            ->addChoiceType($this->getTimeouts());

        $helper->field(self::MESSAGE_SUB_TITLE)
            ->label('parameters.fields.messageSubTitle')
            ->updateAttribute('data-default', (int) self::DEFAULT_SUB_TITLE)
            ->addYesNoType();

        $helper->field(self::MIN_MARGIN)
            ->label('parameters.fields.min_margin')
            ->updateAttribute('data-default', self::DEFAULT_MIN_MARGIN * 100)
            ->percent(true)
            ->addPercentType(0);

        // super admin fields
        if ($this->isSuperAdmin()) {
            $helper->field(self::DATE_FORMAT)
                ->label('parameters.fields.dateFormat')
                ->updateOption('format', 'date')
                ->updateAttribute('data-default', FormatUtils::getDateType())
                ->add(DateTimeFormatType::class);

            $helper->field(self::TIME_FORMAT)
                ->label('parameters.fields.timeFormat')
                ->updateOption('format', 'time')
                ->updateAttribute('data-default', FormatUtils::getTimeType())
                ->add(DateTimeFormatType::class);

            $helper->field(self::GROUPING_SEPARATOR)
                ->label('parameters.fields.grouping')
                ->updateAttribute('data-default', FormatUtils::getGrouping())
                ->add(GroupingSeparatorType::class);

            $helper->field(self::DECIMAL_SEPARATOR)
                ->label('parameters.fields.decimal')
                ->updateAttribute('data-default', FormatUtils::getDecimal())
                ->add(DecimalSeparatorType::class);

            $captcha = (int) !$this->application->isDebug();
            $helper->field(self::DISPLAY_CAPTCHA)
                ->label('parameters.fields.displayCaptcha')
                ->updateAttribute('data-default', $captcha)
                ->addChoiceType([
                    'parameters.displayCaptcha.show' => true,
                    'parameters.displayCaptcha.hide' => false,
                ]);
        }
    }

    /**
     * Formats a date.
     *
     * @param string    $key      the translation id
     * @param int       $datetype the date type
     * @param \DateTime $date     the date to format
     *
     * @return string the formatted date
     */
    private function formatDate(string $key, int $datetype, \DateTime $date): string
    {
        $date = $this->localeDate($date, $datetype);

        return $this->trans($key, [
            '%date%' => $date,
        ]);
    }

    /**
     * Formats a a date as time.
     *
     * @param string    $key      the translation id
     * @param int       $timeType the time type
     * @param \DateTime $date     the date to format
     *
     * @return string the formatted date
     */
    private function formatTime(string $key, int $timeType, \DateTime $date): string
    {
        $date = $this->localeTime($date, $timeType);

        return $this->trans($key, [
            '%date%' => $date,
        ]);
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

    /**
     * Returns if the current user has the super admin role (ROLE_SUPER_ADMIN).
     */
    private function isSuperAdmin(): bool
    {
        if ($user = $this->security->getUser()) {
            if ($user instanceof IRole) {
                return $user->isSuperAdmin();
            }
        }

        return false;
    }
}
