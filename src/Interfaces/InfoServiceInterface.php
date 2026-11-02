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

namespace App\Interfaces;

/**
 * Contains common constant information.
 */
interface InfoServiceInterface
{
    /** The disabled label. */
    public const string LABEL_DISABLED = 'Disabled';

    /** The enabled label. */
    public const string LABEL_ENABLED = 'Enabled';

    /** The not installed label. */
    public const string LABEL_NOT_INSTALLED = 'Not installed';

    /** The no value label. */
    public const string NO_VALUE = 'No value';

    /** The none value label. */
    public const string NONE_VALUE = 'None';

    /** The unknown label */
    public const string UNKNOWN = 'Unknown';
}
