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

use App\Controller\AbstractController;
use App\Report\HtmlReport;
use PHPUnit\Framework\TestCase;

class HtmlReportTest extends TestCase
{
    public function testRender(): void
    {
        $html = <<<HTML
                <html>
                    <body>
                        <i>Test</i>
                        <br>
                        <div>Text</div><
                    </body>
                </html>
            HTML;
        $controller = $this->createMock(AbstractController::class);
        $report = new HtmlReport($controller, $html);
        $report->addPage();
        $report->updateLeftMargin($report->getLeftMargin());
        $report->updateRightMargin($report->getRightMargin());
        $report->setLeftMargin(0.0);
        $report->setRightMargin(0.0);
        $report->addPage();
        $report->setLeftMargin(20.0);
        $report->setRightMargin(20.0);
        $report->addPage();
        $actual = $report->render();

        self::assertTrue($actual);
    }

    public function testRenderEmpty(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new HtmlReport($controller, '');
        $actual = $report->render();
        self::assertFalse($actual);
    }

    public function testRenderList(): void
    {
        $html = <<<HTML
                <html>
                    <body>
                        <ul>
                          <li>Milk</li>
                          <li>
                            Cheese
                            <ul>
                              <li>Blue cheese</li>
                              <li>Feta</li>
                            </ul>
                          </li>
                        </ul>
                        <ol>
                            <li>Mix flour, baking powder, sugar, and salt.</li>
                            <li>In another bowl, mix eggs, milk, and oil.</li>
                            <li>Stir both mixtures together.</li>
                            <li>Fill muffin tray 3/4 full.</li>
                            <li>Bake for 20 minutes.
                                <ol>
                                    <li>Stir both mixtures together.</li>
                                    <li>Fill muffin tray 3/4 full.</li>
                                </ol>
                            </li>
                        </ol>
                    </body>
                </html>
            HTML;

        $controller = $this->createMock(AbstractController::class);
        $report = new HtmlReport($controller, $html);
        $report->addPage();
        $actual = $report->render();
        self::assertTrue($actual);
    }

    public function testRenderSpace(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $report = new HtmlReport($controller, ' ');
        $actual = $report->render();
        self::assertFalse($actual);
    }
}
