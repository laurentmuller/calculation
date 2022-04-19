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

namespace App\Enums;

/**
 * Entity action enumeration.
 */
enum EntityAction: string
{
    /*
     * Edit the entity.
     */
    case EDIT = 'edit';
    /*
     * No action.
     */
    case NONE = 'none';
    /*
     * Show the entity.
     */
    case SHOW = 'show';
}
