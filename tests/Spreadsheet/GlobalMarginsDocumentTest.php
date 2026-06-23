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

namespace App\Tests\Spreadsheet;

use App\Entity\GlobalMargin;
use App\Interfaces\DocumentHelperInterface;
use App\Spreadsheet\GlobalMarginsDocument;
use PHPUnit\Framework\TestCase;

final class GlobalMarginsDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $margins = new GlobalMargin();
        $margins->setMaximum(100.0)
            ->setMargin(1.1);

        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new GlobalMarginsDocument($helper, [$margins]);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $helper = self::createStub(DocumentHelperInterface::class);
        $document = new GlobalMarginsDocument($helper, []);
        $actual = $document->render();
        self::assertFalse($actual);
    }
}
