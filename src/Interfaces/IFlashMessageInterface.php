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

namespace App\Interfaces;

/**
 * Constants used to add flashbag messages.
 */
interface IFlashMessageInterface
{
    /**
     * The error flash message type.
     */
    public const FLASH_TYPE_ERROR = 'danger';

    /**
     * The info flash message type.
     */
    public const FLASH_TYPE_INFO = 'info';

    /**
     * The success flash message type.
     */
    public const FLASH_TYPE_SUCCESS = 'success';

    /**
     * The warning flash message type.
     */
    public const FLASH_TYPE_WARNING = 'warning';
}
