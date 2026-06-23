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
use App\Response\WordResponse;
use App\Utils\StringUtils;
use App\Word\AbstractWordDocument;
use App\Word\WordDocument;

/**
 * Trait to render a Word document.
 *
 * @phpstan-require-extends AbstractController
 */
trait RenderWordDocumentTrait
{
    /**
     * Render the given Word document and output the response.
     *
     * @param WordDocument $doc    the document to render
     * @param bool         $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used
     *                             if available. <code>false</code> to send to the browser and force a file download
     *                             with the name given.
     * @param string       $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderWordDocument(WordDocument $doc, bool $inline = true, string $name = ''): WordResponse
    {
        if ($doc instanceof AbstractWordDocument && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getTitle())) {
            $name = $doc->getTitle();
        }

        return new WordResponse($doc, $inline, $name);
    }
}
