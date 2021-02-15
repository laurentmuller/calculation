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

namespace App\Pdf\Enum;

use MyCLabs\Enum\Enum;

/**
 * The document unit enumeration.
 *
 * @method static Unit CENTIMETER() The centimeter document unit.
 * @method static Unit INCH()       The inch document unit.
 * @method static Unit MILLIMETER() The millimeter document unit.
 * @method static Unit POINT()      The point document unit.
 *
 * @author Laurent Muller
 */
class Unit extends Enum
{
    /**
     * The centimeter document unit.
     */
    private const CENTIMETER = 'cm';

    /**
     * The inch document unit.
     */
    private const INCH = 'in';

    /**
     * The millimeter document unit.
     */
    private const MILLIMETER = 'mm';

    /**
     * The point document unit.
     */
    private const POINT = 'pt';
}
