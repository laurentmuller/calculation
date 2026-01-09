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

use App\Enums\MessagePosition;

final class UserParametersControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/user/parameters', self::ROLE_USER];
        yield ['/user/parameters', self::ROLE_ADMIN];
        yield ['/user/parameters', self::ROLE_SUPER_ADMIN];
    }

    public function testParametersNoChange(): void
    {
        $this->checkForm('user/parameters');
    }

    public function testParametersWithChanges(): void
    {
        $data = ['message[position]' => MessagePosition::TOP_LEFT->value];
        $this->checkForm(
            uri: 'user/parameters',
            data: $data
        );
    }
}
