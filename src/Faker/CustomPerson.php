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
 * Faker provider to generate custom person.
 *
 * @author Laurent Muller
 */
class CustomPerson extends \Faker\Provider\fr_CH\Person
{
    /**
     * @var string[]
     */
    protected static $titleFemale = ['Madame', 'Mademoiselle'];

    /**
     * @var string[]
     */
    protected static $titleMale = ['Monsieur'];
}
