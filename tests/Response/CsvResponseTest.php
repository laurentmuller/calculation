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

namespace App\Tests\Response;

use App\Response\AbstractStreamedResponse;
use App\Response\CsvResponse;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CsvResponse::class)]
#[CoversClass(AbstractStreamedResponse::class)]
class CsvResponseTest extends TestCase
{
    public function testGetFileExtension(): void
    {
        $response = new CsvResponse(null);
        self::assertSame('csv', $response->getFileExtension());
    }
}
