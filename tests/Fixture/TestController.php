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

namespace App\Tests\Fixture;

use App\Controller\AbstractController;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Response\WordResponse;
use App\Spreadsheet\SpreadsheetDocument;
use App\Word\WordDocument;
use fpdf\PdfDocument;
use Psr\Container\ContainerInterface;

/**
 * Controller for tests with public methods.
 */
class TestController extends AbstractController
{
    public function __construct(ContainerInterface $container)
    {
        $this->setContainer($container);
    }

    #[\Override]
    public function renderPdfDocument(
        PdfDocument $doc,
        bool $inline = true,
        string $name = ''
    ): PdfResponse {
        return parent::renderPdfDocument($doc, $inline, $name);
    }

    #[\Override]
    public function renderSpreadsheetDocument(
        SpreadsheetDocument $doc,
        bool $inline = true,
        string $name = ''
    ): SpreadsheetResponse {
        return parent::renderSpreadsheetDocument($doc, $inline, $name);
    }

    #[\Override]
    public function renderWordDocument(
        WordDocument $doc,
        bool $inline = true,
        string $name = ''
    ): WordResponse {
        return parent::renderWordDocument($doc, $inline, $name);
    }
}
