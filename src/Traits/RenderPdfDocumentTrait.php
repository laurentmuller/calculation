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
use App\Report\AbstractReport;
use App\Response\PdfResponse;
use App\Utils\StringUtils;
use fpdf\PdfDocument;

/**
 * Trait to render a PDF document.
 *
 * @phpstan-require-extends AbstractController
 */
trait RenderPdfDocumentTrait
{
    /**
     * Render the given PDF document and output the response.
     *
     * @param PdfDocument $doc    the document to render
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if
     *                            available. <code>false</code> to send to the browser and force a file download with
     *                            the name given.
     * @param string      $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        if ($doc instanceof AbstractReport && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getProperties()->getTitle())) {
            $name = $doc->getProperties()->getTitle();
        }

        return new PdfResponse($doc, $inline, $name);
    }
}
