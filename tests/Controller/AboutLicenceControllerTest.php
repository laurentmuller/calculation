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

namespace App\Tests\Controller;

final class AboutLicenceControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/about/licence'];
        yield ['/about/licence', self::ROLE_USER];
        yield ['/about/licence', self::ROLE_ADMIN];
        yield ['/about/licence', self::ROLE_SUPER_ADMIN];

        yield ['/about/licence/content', self::ROLE_USER];
        yield ['/about/licence/content', self::ROLE_ADMIN];
        yield ['/about/licence/content', self::ROLE_SUPER_ADMIN];

        yield ['/about/licence/pdf'];
        yield ['/about/licence/pdf', self::ROLE_USER];
        yield ['/about/licence/pdf', self::ROLE_ADMIN];
        yield ['/about/licence/pdf', self::ROLE_SUPER_ADMIN];

        yield ['/about/licence/word'];
        yield ['/about/licence/word', self::ROLE_USER];
        yield ['/about/licence/word', self::ROLE_ADMIN];
        yield ['/about/licence/word', self::ROLE_SUPER_ADMIN];
    }
}
