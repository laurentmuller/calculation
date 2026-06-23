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

namespace App\Traits;

use App\Controller\AbstractController;
use App\Response\SpreadsheetResponse;
use App\Spreadsheet\AbstractDocument;
use App\Spreadsheet\SpreadsheetDocument;
use App\Utils\StringUtils;

/**
 * Trait to render a Spreadsheet document.
 *
 * @phpstan-require-extends AbstractController
 */
trait RenderSpreadsheetDocumentTrait
{
    /**
     * Render the given Spreadsheet document and output the response.
     *
     * @param SpreadsheetDocument $doc    the document to render
     * @param bool                $inline <code>true</code> to send the file inline to the browser. The Spreadsheet
     *                                    viewer is used if available.
     *                                    <code>false</code> to send to the browser and force a file download.
     * @param string              $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderSpreadsheetDocument(
        SpreadsheetDocument $doc,
        bool $inline = true,
        string $name = ''
    ): SpreadsheetResponse {
        if ($doc instanceof AbstractDocument && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getTitle())) {
            $name = $doc->getTitle();
        }

        return new SpreadsheetResponse($doc, $inline, $name);
    }
}
