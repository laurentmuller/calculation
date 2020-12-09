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

namespace App\Interfaces;

/**
 * Entity action constants.
 *
 * @author Laurent Muller
 */
interface ActionInterface
{
    /**
     * Edit the entity.
     */
    public const ACTION_EDIT = 'edit';

    /**
     * No action.
     */
    public const ACTION_NONE = 'none';

    /**
     * Show the entity.
     */
    public const ACTION_SHOW = 'show';
}
