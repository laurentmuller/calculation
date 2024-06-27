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

use App\Controller\AjaxTranslateController;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(AjaxTranslateController::class)]
class AjaxTranslateControllerTest extends AbstractControllerTestCase
{
    public static function getRoutes(): \Iterator
    {
        yield ['/ajax/detect', self::ROLE_USER];
        yield ['/ajax/detect?text=hello', self::ROLE_USER];
        yield ['/ajax/languages', self::ROLE_USER];
    }

    public function testTranslateEmptyText(): void
    {
        $parameters = [
            'text' => '',
            'from' => '',
            'to' => '',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateEmptyTo(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => '',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateInvalidService(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
            'service' => 'fake',
        ];
        $this->checkTranslate($parameters);
    }

    public function testTranslateSuccess(): void
    {
        $parameters = [
            'text' => 'Hello',
            'from' => 'en',
            'to' => 'fr',
        ];
        $this->checkTranslate($parameters);
    }

    private function checkTranslate(array $parameters): void
    {
        $this->checkRoute(
            '/ajax/translate',
            self::ROLE_USER,
            method: Request::METHOD_POST,
            xmlHttpRequest: true,
            parameters: $parameters
        );
    }
}
