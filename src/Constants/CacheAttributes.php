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

namespace App\Constants;

/**
 * Contains cache constants.
 */
final class CacheAttributes
{
    // cache names
    public const string CACHE_ASSET = 'calculation.asset';
    public const string CACHE_COMMAND = 'calculation.command';
    public const string CACHE_CONSTANT = 'calculation.constant';
    public const string CACHE_FONT_AWESOME = 'calculation.fontawesome';
    public const string CACHE_HELP = 'calculation.help';
    public const string CACHE_LOG = 'calculation.log';
    public const string CACHE_PARAMETERS = 'calculation.parameters';
    public const string CACHE_RESPONSE = 'calculation.response';
    public const string CACHE_SCHEMA = 'calculation.schema';
    public const string CACHE_SEARCH = 'calculation.search';
    public const string CACHE_SERVICE = 'calculation.service';
    public const string CACHE_SYMFONY = 'calculation.symfony';
    public const string CACHE_USER = 'calculation.user';

    // cache lifetime in seconds
    public const int LIFE_TIME_FIFTEEN_MINUTES = 900;
    public const int LIFE_TIME_ONE_DAY = 86_400;
    public const int LIFE_TIME_ONE_HOUR = 3_600;
    public const int LIFE_TIME_ONE_MONTH = 2_592_000;
}
