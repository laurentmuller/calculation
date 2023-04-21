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

namespace App\Word;

use App\Controller\AbstractController;
use PhpOffice\PhpWord\Shared\Html;

/**
 * Document to output HTML content.
 */
class HtmlDocument extends AbstractWordDocument
{
    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent's controller
     * @param string             $content    the HTML content to render
     */
    public function __construct(AbstractController $controller, private readonly string $content)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if ('' === $this->content) {
            return false;
        }

        $this->addHeaderStyles();
        $parser = new HtmlWordParser();
        $content = $parser->parse($this->content);
        $section = $this->createDefaultSection();
        Html::addHtml($section, $content, false, false);

        return true;
    }

    private function addHeaderStyles(): void
    {
        $fontStyle = ['bold' => true];
        $paragraphStyle = ['keepNext' => true];
        foreach (\range(1, 6) as $index) {
            $fontStyle['size'] = 20 - 2 * ($index - 1);
            $this->addTitleStyle($index, $fontStyle, $paragraphStyle);
        }
    }
}
