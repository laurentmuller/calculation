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

use App\Controller\AbstractController;
use App\Entity\GlobalMargin;
use App\Spreadsheet\GlobalMarginsDocument;
use PHPUnit\Framework\TestCase;

class GlobalMarginsDocumentTest extends TestCase
{
    public function testRender(): void
    {
        $margins = new GlobalMargin();
        $margins->setMaximum(100.0)
            ->setMargin(1.1);

        $controller = $this->createMock(AbstractController::class);
        $document = new GlobalMarginsDocument($controller, [$margins]);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $document = new GlobalMarginsDocument($controller, []);
        $actual = $document->render();
        self::assertFalse($actual);
    }
}
