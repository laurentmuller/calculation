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
use App\Enums\ImageExtension;
use App\Enums\ImageSize;
use App\Service\UserNamer;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;

#[\PHPUnit\Framework\Attributes\CoversClass(UserNamer::class)]
class UserNamerTest extends TestCase
{
    public static function getBaseNames(): array
    {
        return [
            ['USER_000001_192', 1, ImageSize::DEFAULT],
            ['USER_000001_192.png', 1, ImageSize::DEFAULT, 'png'],
            ['USER_000001_192.png', 1, ImageSize::DEFAULT, ImageExtension::PNG],

            ['USER_000001_096', 1, ImageSize::MEDIUM],
            ['USER_000001_096.png', 1, ImageSize::MEDIUM, 'png'],
            ['USER_000001_096.png', 1, ImageSize::MEDIUM, ImageExtension::PNG],

            ['USER_000001_032', 1, ImageSize::SMALL],
            ['USER_000001_032.png', 1, ImageSize::SMALL, 'png'],
            ['USER_000001_032.png', 1, ImageSize::SMALL, ImageExtension::PNG],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('getBaseNames')]
    public function testBaseName(string $expected, int $value, ImageSize $size, ImageExtension|string $ext = null): void
    {
        $result = UserNamer::getBaseName($value, $size, $ext);
        $this->assertSame($result, $expected);
    }

    public function testName(): void
    {
        $user = new User();
        $namer = new UserNamer();
        $mapping = new PropertyMapping('', '');
        $result = $namer->name($user, $mapping);
        $this->assertSame($result, 'USER_000000_192.png');
    }
}