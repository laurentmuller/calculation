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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\Enums\PdfDocumentOrientation;
use App\Pdf\Enums\PdfDocumentSize;
use App\Pdf\Enums\PdfDocumentUnit;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfDocument;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Traits\TranslatorTrait;
use App\Twig\FormatExtension;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract report.
 */
abstract class AbstractReport extends PdfDocument
{
    use TranslatorTrait;

    private readonly FormatExtension $extension;
    private readonly TranslatorInterface $translator;

    /**
     * Constructor.
     *
     * @param PdfDocumentOrientation $orientation the page orientation
     * @param PdfDocumentUnit        $unit        the user unit
     * @param PdfDocumentSize        $size        the document size
     */
    public function __construct(
        protected AbstractController $controller,
        PdfDocumentOrientation $orientation = PdfDocumentOrientation::PORTRAIT,
        PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER,
        PdfDocumentSize $size = PdfDocumentSize::A4
    ) {
        parent::__construct($orientation, $unit, $size);
        $this->translator = $this->controller->getTranslator();
        $this->extension = new FormatExtension($this->translator);
        $appName = $controller->getApplicationName();
        $this->SetCreator($appName);
        $userName = $controller->getUserIdentifier();
        if (null !== $userName) {
            $this->SetAuthor($userName);
        }
        $service = $this->controller->getUserService();
        $this->getHeader()->setCustomer($service->getCustomer());
        $this->getFooter()->setContent($appName, $controller->getApplicationOwnerUrl());
    }

    /**
     * {@inheritdoc}
     *
     * Override the default behavior by adding a translated title if null and the page index to bookmarks.
     */
    public function addPageIndex(
        string $title = null,
        PdfStyle $titleStyle = null,
        PdfStyle $contentStyle = null,
        bool $addBookmark = true,
        string $separator = '.'
    ): self {
        $title ??= $this->trans('report.index');
        parent::addPageIndex($title, $titleStyle, $contentStyle, $addBookmark, $separator);

        return $this;
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool    $value     the value to format
     * @param ?string $true      the text to use when the value is <b>TRUE</b> or <code>null</code> to use default
     * @param ?string $false     the text to use when the value is <b>FALSE</b> or <code>null</code> to use default
     * @param bool    $translate <code>TRUE</code> to translate texts
     */
    public function formatBoolean(bool $value, string $true = null, string $false = null, bool $translate = false): string
    {
        return $this->extension->formatBoolean($value, $true, $false, $translate);
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Render this document.
     *
     * @return bool true if rendered successfully
     */
    abstract public function render(): bool;

    /**
     * Renders a single line with the given number of elements.
     *
     * @return bool true if the given number of elements is greater than 0
     */
    public function renderCount(PdfTable $table, \Countable|array|int $count, string $message = 'common.count'): bool
    {
        $this->resetStyle();
        $text = $this->translateCount($count, $message);
        $table->singleLine($text, PdfStyle::getHeaderStyle(), PdfTextAlignment::LEFT);

        return $this->toInt($count) > 0;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string $id     the title id (may also be an object that can be cast to string)
     * @param bool   $isUTF8 indicates if the title is encoded in ISO-8859-1 (false) or UTF-8 (true)
     */
    public function setTitleTrans(string $id, array $parameters = [], bool $isUTF8 = false, string $domain = null, string $locale = null): static
    {
        $title = $this->trans($id, $parameters, $domain, $locale);
        $this->SetTitle($title, $isUTF8);

        return $this;
    }

    /**
     * Gets the translated count text.
     */
    protected function translateCount(\Countable|array|int $count, string $message = 'common.count'): string
    {
        return $this->trans($message, ['%count%' => $this->toInt($count)]);
    }

    private function toInt(\Countable|array|int $value): int
    {
        return \is_int($value) ? $value : \count($value);
    }
}
