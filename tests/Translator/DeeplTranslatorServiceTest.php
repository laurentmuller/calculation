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

namespace App\Tests\Translator;

use App\Translator\DeeplTranslatorService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;

class DeeplTranslatorServiceTest extends TestCase
{
    public function testGetApiURL(): void
    {
        $actual = DeeplTranslatorService::getApiUrl();
        self::assertSame('https://developers.deepl.com/docs', $actual);
    }

    public function testGetName(): void
    {
        $service = new DeeplTranslatorService(
            'apikey',
            $this->createMock(CacheInterface::class),
            $this->createMock(LoggerInterface::class),
        );
        $actual = $service->getName();
        self::assertSame('DeepL', $actual);
    }
}
