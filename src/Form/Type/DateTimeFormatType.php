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

namespace App\Form\Type;

use App\Traits\DateFormatterTrait;
use App\Traits\TranslatorTrait;
use IntlDateFormatter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Loader\CallbackChoiceLoader;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Type to select a date or a time format.
 *
 * @author Laurent Muller
 */
class DateTimeFormatType extends AbstractType
{
    use DateFormatterTrait;
    use TranslatorTrait;

    /**
     * Constructor.
     *
     * @param TranslatorInterface $translator the translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault('choice_translation_domain', false)
            ->setDefault('format', 'date')
            ->setAllowedTypes('format', 'string')
            ->setAllowedValues('format', ['date', 'time'])
            ->setDefault('choice_loader', function (Options $options) {
                $useDate = $this->isUseDate($options);

                return new CallbackChoiceLoader(function () use ($useDate) {
                    return $this->loadValues($useDate);
                });
            });
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return ChoiceType::class;
    }

    /**
     * Formats the given date.
     *
     * @param string    $key     the translation id
     * @param int       $type    the date or time type
     * @param bool      $useDate <code>true</code> to use date format; <code>false</code> to use time format
     * @param \DateTime $date    the date to format
     *
     * @return string the formatted date
     */
    private function format(string $key, int $type, bool $useDate, \DateTime $date): string
    {
        $date = $useDate ? $this->localeDate($date, $type) : $this->localeTime($date, $type);

        return $this->trans($key, ['%date%' => $date]);
    }

    /**
     * Returns if the date format is used.
     *
     * @param Options $options the form options
     *
     * @return bool <code>true</code> if date format must be used; <code>false</code> if time format
     */
    private function isUseDate(Options $options)
    {
        return 'date' === ($options['format'] ?? 'date');
    }

    /**
     * Loads choice values.
     *
     * @param bool $useDate <code>true</code> to get date formats; <code>false</code> to get time formats
     *
     * @return array the choice values
     */
    private function loadValues(bool $useDate): array
    {
        $date = new \DateTime();
        $values = [
            $this->format('date_format.short', IntlDateFormatter::SHORT, $useDate, $date) => IntlDateFormatter::SHORT,
            $this->format('date_format.medium', IntlDateFormatter::MEDIUM, $useDate, $date) => IntlDateFormatter::MEDIUM,
        ];
        if ($useDate) {
            $values[$this->format('date_format.long', IntlDateFormatter::LONG, $useDate, $date)] = IntlDateFormatter::LONG;
        }

        return $values;
    }
}
