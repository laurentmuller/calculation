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

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\UserNamer;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;

final class UserNamerTest extends TestCase
{
    public function testName(): void
    {
        $user = new User();
        $namer = new UserNamer();
        $mapping = new PropertyMapping('', '');
        $actual = $namer->name($user, $mapping);
        self::assertSame('USER_000000_192.png', $actual);
    }
}
