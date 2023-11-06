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

namespace App\Tests\Attribute;

use App\Attribute\DeleteRoute;
use App\Attribute\EditRoute;
use App\Attribute\GetRoute;
use App\Attribute\PostRoute;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[\PHPUnit\Framework\Attributes\CoversClass(DeleteRoute::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(EditRoute::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(GetRoute::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(PostRoute::class)]
class RouteAttributeTest extends TestCase
{
    public function testRoutes(): void
    {
        $this->validate(new DeleteRoute(), Request::METHOD_GET, Request::METHOD_DELETE);
        $this->validate(new EditRoute(), Request::METHOD_GET, Request::METHOD_POST);
        $this->validate(new GetRoute(), Request::METHOD_GET);
        $this->validate(new PostRoute(), Request::METHOD_POST);
    }

    private function validate(Route $route, string ...$expected): void
    {
        self::assertEqualsCanonicalizing($expected, $route->getMethods());
    }
}
