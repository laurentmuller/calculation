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

namespace App\Traits;

use App\Service\ApplicationService;
use App\Utils\FormatUtils;

/**
 * Combined the date and the number formatter traits.
 *
 * @author Laurent Muller
 */
trait FormatterTrait
{
    use DateFormatterTrait;
    use NumberFormatterTrait;

    /**
     * The application service.
     *
     * @var ApplicationService
     */
    protected $application;

    /**
     * Gets the default currency symbol. Override the NumberFormatterTrait function.
     */
    protected function getDefaultCurrency(): string
    {
        if ($application = $this->doGetApplication()) {
            return $application->getCurrency();
        }

        return FormatUtils::getCurrency();
    }

    /**
     * Gets the default date type format. Override the DateFormatterTrait function.
     *
     * @return int type of date formatting, one of the format type constants
     */
    protected function getDefaultDateType(): int
    {
        if ($application = $this->doGetApplication()) {
            return $application->getDateFormat();
        }

        return FormatUtils::getDateType();
    }

    /**
     * Gets the default decimal separator. Override the NumberFormatterTrait function.
     *
     * @return string the decimal separator
     */
    protected function getDefaultDecimal(): string
    {
        if ($application = $this->doGetApplication()) {
            return $application->getDecimal();
        }

        return FormatUtils::getDecimal();
    }

    /**
     * Gets the default grouping separator. Override the NumberFormatterTrait function.
     *
     * @return string the grouping separator
     */
    protected function getDefaultGrouping(): string
    {
        if ($application = $this->doGetApplication()) {
            return $application->getGrouping();
        }

        return FormatUtils::getGrouping();
    }

    /**
     * Gets the default time type format. Override the DateFormatterTrait function.
     *
     * @return int type of time formatting, one of the format type constants
     */
    protected function getDefaultTimeType(): int
    {
        if ($application = $this->doGetApplication()) {
            return $application->getTimeFormat();
        }

        return FormatUtils::getTimeType();
    }

    /**
     * Gets the application.
     *
     * @return ApplicationService|null the application if found; null otherwise
     */
    private function doGetApplication(): ?ApplicationService
    {
        if (!$this->application && \method_exists($this, 'getApplication')) {
            return $this->application = $this->getApplication();
        }

        return $this->application;
    }
}
