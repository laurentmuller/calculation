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

namespace App\Parameter;

/**
 * Property type enumeration.
 */
enum PropertyType
{
    case ARRAY;
    case BOOL;
    case DATE;
    case ENUM_INT;
    case ENUM_STRING;
    case FLOAT;
    case INTEGER;
    case STRING;
}
