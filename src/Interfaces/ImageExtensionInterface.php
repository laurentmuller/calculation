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
 * The images constants.
 *
 * @author Laurent Muller
 */
interface ImageExtensionInterface
{
    /**
     * The default image resolution (96) in dot per each (DPI).
     */
    public const DEFAULT_RESOLUTION = 96;

    /**
     * The Bitmap file extension ("bmp").
     */
    public const EXTENSION_BMP = 'bmp';

    /**
     * The Gif file extension ("gif").
     */
    public const EXTENSION_GIF = 'gif';

    /**
     * The JPEG file extension ("jpeg").
     */
    public const EXTENSION_JPEG = 'jpeg';

    /**
     * The JPG file extension ("jpg").
     */
    public const EXTENSION_JPG = 'jpg';

    /**
     * The PNG file extension ("png").
     */
    public const EXTENSION_PNG = 'png';

    /**
     * The XBM file extension ("xbm").
     */
    public const EXTENSION_XBM = 'xbm';

    /**
     * The default image size (192 pixels).
     */
    public const SIZE_DEFAULT = 192;

    /**
     * The medium image size used for user list (96 pixels).
     */
    public const SIZE_MEDIUM = 96;

    /**
     * The small image size used for logged user (32 pixels).
     */
    public const SIZE_SMALL = 32;
}
