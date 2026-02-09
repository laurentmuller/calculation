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

namespace App\Service;

use Symfony\Component\Mime\Address;

/**
 * Service to get application properties.
 */
readonly class ApplicationService
{
    public const string APP_DESCRIPTION = "Programme de calcul basé sur l'environnement de développement Symfony 7.x.";
    public const string APP_FULL_NAME = self::APP_NAME . ' v' . self::APP_VERSION;
    public const string APP_NAME = 'Calculation';
    public const string APP_VERSION = '3.0.0';

    public const string OWNER_CITY = 'Montévraz';
    public const string OWNER_EMAIL = 'calculation@bibi.nu';
    public const string OWNER_NAME = 'bibi.nu';
    public const string OWNER_URL = 'https://www.bibi.nu';

    public static function getOwnerAddress(): Address
    {
        return new Address(self::OWNER_EMAIL, self::APP_NAME);
    }
}
