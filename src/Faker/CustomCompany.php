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

namespace App\Faker;

/**
 * Faker provider to generate company names.
 *
 * @author Laurent Muller
 */
class CustomCompany extends \Faker\Provider\fr_CH\Company
{
    /**
     * Returns the company name and company suffix.
     *
     * @return string
     */
    public function companyAndSuffix()
    {
        return $this->company().' '.$this->companySuffix();
    }
}
