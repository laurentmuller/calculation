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
use App\Pdf\Enums\PdfMove;
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfDocument;
use App\Traits\TranslatorTrait;
use App\Twig\FormatExtension;
use App\Util\FormatUtils;
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
     * @param PdfDocumentOrientation|string $orientation the page orientation
     * @param PdfDocumentUnit|string        $unit        the measure unit
     * @param PdfDocumentSize|int[]         $size        the document size or the width and height of the document
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(protected AbstractController $controller, PdfDocumentOrientation|string $orientation = PdfDocumentOrientation::PORTRAIT, PdfDocumentUnit|string $unit = PdfDocumentUnit::MILLIMETER, PdfDocumentSize|array $size = PdfDocumentSize::A4)
    {
        parent::__construct($orientation, $unit, $size);
        $this->translator = $this->controller->getTranslator();
        $this->extension = new FormatExtension($this->translator);
        $appName = $controller->getApplicationName();

        // meta-data
        $this->SetCreator($appName);
        if (null !== $userName = $controller->getUserIdentifier()) {
            $this->SetAuthor($userName);
        }

        // header
        $service = $this->controller->getUserService();
        $this->header->setCustomer($service->getCustomer());

        // footer
        $this->footer->setContent($appName, $controller->getApplicationOwnerUrl());
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool    $value     the value to format
     * @param ?string $true      the text to use when the value is <b>TRUE</b> or <code>null</code> to use default
     * @param ?string $false     the text to use when the value is <b>FALSE</b> or <code>null</code> to use default
     * @param bool    $translate <code>TRUE</code> to translate texts
     */
    public function booleanFilter(bool $value, ?string $true = null, ?string $false = null, bool $translate = false): string
    {
        return $this->extension->booleanFilter($value, $true, $false, $translate);
    }

    /**
     * {@inheritDoc}
     */
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
     * Renders a line with the given number of elements.
     *
     * @param \Countable|array|int $count      the number of elements, an array or a \Countable object
     * @param PdfTextAlignment     $align      the text alignment
     * @param bool                 $resetStyle true to reset style before output the line
     *
     * @return bool true if the number of elements is greater than 0
     */
    public function renderCount(\Countable|array|int $count, PdfTextAlignment $align = PdfTextAlignment::LEFT, bool $resetStyle = true): bool
    {
        if ($resetStyle) {
            $this->resetStyle();
        }
        $text = $this->translateCount($count);
        $margins = $this->setCellMargin(0);
        $this->Cell(0, self::LINE_HEIGHT, $text, PdfBorder::none(), PdfMove::NEW_LINE, $align);
        $this->setCellMargin($margins);

        return (\is_countable($count) ? \count($count) : $count) > 0;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string $id     the title id (may also be an object that can be cast to string)
     * @param bool   $isUTF8 true to encode to UTF-8
     */
    public function setTitleTrans(string $id, array $parameters = [], bool $isUTF8 = false, ?string $domain = null, ?string $locale = null): static
    {
        $title = $this->trans($id, $parameters, $domain, $locale);
        $this->SetTitle($title, $isUTF8);

        return $this;
    }

    /**
     * Gets the translated count label.
     *
     * @param \Countable|array|int $count the number of elements
     */
    protected function translateCount(\Countable|array|int $count): string
    {
        if (\is_countable($count)) {
            $count = \count($count);
        }

        return $this->trans('common.count', ['%count%' => FormatUtils::formatInt($count)]);
    }
}
