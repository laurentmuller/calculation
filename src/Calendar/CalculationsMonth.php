<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
