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

namespace App\Calendar;

/**
 * Extends the month class with an array of calculations.
 *
 * @author Laurent Muller
 */
class CalculationsMonth extends Month implements \Countable
{
    use CalculationsTrait;
}
