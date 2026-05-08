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

use App\Service\DictionaryService;
use Symfony\Component\HttpFoundation\Response;

final class AdminParametersControllerTest extends ControllerTestCase
{
    #[\Override]
    public static function getRoutes(): \Generator
    {
        yield ['/admin/parameters', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/admin/parameters', self::ROLE_ADMIN];
        yield ['/admin/parameters', self::ROLE_SUPER_ADMIN];
    }

    public function testParametersNoChange(): void
    {
        $this->checkForm(
            uri: 'admin/parameters',
            userName: self::ROLE_SUPER_ADMIN
        );
    }

    public function testParametersWithChanges(): void
    {
        $name = $this->getService(DictionaryService::class)
            ->getRandomWord();
        $data = ['customer[name]' => $name];
        $this->checkForm(
            uri: 'admin/parameters',
            data: $data,
            userName: self::ROLE_SUPER_ADMIN
        );
    }
}
