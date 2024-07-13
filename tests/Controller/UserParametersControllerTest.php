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
use App\Interfaces\PropertyServiceInterface;

class UserParametersControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user/parameters', self::ROLE_USER];
        yield ['/user/parameters', self::ROLE_ADMIN];
        yield ['/user/parameters', self::ROLE_SUPER_ADMIN];
    }

    public function testParametersNoChange(): void
    {
        $this->checkForm(
            'user/parameters'
        );
    }

    public function testParametersWithChanges(): void
    {
        $data = [PropertyServiceInterface::P_MESSAGE_POSITION => MessagePosition::TOP_LEFT->value];
        $this->checkForm(
            'user/parameters',
            data: $data
        );
    }
}
