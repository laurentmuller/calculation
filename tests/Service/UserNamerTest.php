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
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Vich\UploaderBundle\Mapping\PropertyMapping;

#[CoversClass(UserNamer::class)]
class UserNamerTest extends TestCase
{
    public static function getBaseNames(): \Iterator
    {
        yield ['USER_000001_192', 1, ImageSize::DEFAULT];
        yield ['USER_000001_192.png', 1, ImageSize::DEFAULT, 'png'];
        yield ['USER_000001_192.png', 1, ImageSize::DEFAULT, ImageExtension::PNG];
        yield ['USER_000001_096', 1, ImageSize::MEDIUM];
        yield ['USER_000001_096.png', 1, ImageSize::MEDIUM, 'png'];
        yield ['USER_000001_096.png', 1, ImageSize::MEDIUM, ImageExtension::PNG];
        yield ['USER_000001_032', 1, ImageSize::SMALL];
        yield ['USER_000001_032.png', 1, ImageSize::SMALL, 'png'];
        yield ['USER_000001_032.png', 1, ImageSize::SMALL, ImageExtension::PNG];
    }

    #[DataProvider('getBaseNames')]
    public function testBaseName(string $expected, int $value, ImageSize $size, ImageExtension|string|null $ext = null): void
    {
        $result = UserNamer::getBaseName($value, $size, $ext);
        self::assertSame($result, $expected);
    }

    public function testName(): void
    {
        $user = new User();
        $namer = new UserNamer();
        $mapping = new PropertyMapping('', '');
        $result = $namer->name($user, $mapping);
        self::assertSame($result, 'USER_000000_192.png');
    }
}
