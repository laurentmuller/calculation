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

use App\Service\CurlService;
use PHPUnit\Framework\TestCase;

final class CurlServiceTest extends TestCase
{
    private const string EXAMPLE_URL = 'https://example.com';

    public function testExecute(): void
    {
        $service = CurlService::instance([
            \CURLOPT_URL => self::EXAMPLE_URL,
            \CURLOPT_NOBODY => true,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => true,
        ]);
        $actual = $service->execute();
        self::assertSame('', $actual);
    }

    public function testGetResponseCode(): void
    {
        $service = CurlService::instance([
            \CURLOPT_URL => self::EXAMPLE_URL,
            \CURLOPT_NOBODY => true,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_FOLLOWLOCATION => true,
        ]);
        $service->execute();
        $actual = $service->getResponseCode();
        self::assertSame(200, $actual);
    }

    public function testIsValidUrl(): void
    {
        $service = CurlService::instance();
        $actual = $service->isValidUrl(self::EXAMPLE_URL);
        self::assertTrue($actual);
    }

    public function testIsValidUrlInvalid(): void
    {
        $service = CurlService::instance();
        $actual = $service->isValidUrl('fake');
        self::assertFalse($actual);
    }

    public function testSetOption(): void
    {
        $service = CurlService::instance();
        $service->setOption(\CURLOPT_RETURNTRANSFER, true);
        $actual = $service->getInfo(\CURLINFO_EFFECTIVE_URL);
        self::assertSame('', $actual);
    }

    public function testSetUrl(): void
    {
        $service = CurlService::instance();
        $service->setUrl(self::EXAMPLE_URL);
        $actual = $service->getEffectiveUrl();
        self::assertSame(self::EXAMPLE_URL, $actual);
    }
}
