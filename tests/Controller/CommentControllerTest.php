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

use App\Controller\CommentController;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CommentController::class)]
class CommentControllerTest extends ControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/user/comment', self::ROLE_USER];
        yield ['/user/comment', self::ROLE_ADMIN];
        yield ['/user/comment', self::ROLE_SUPER_ADMIN];
    }
}
