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
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfBookmarkTrait;
use App\Pdf\Traits\PdfColumnTranslatorTrait;
use App\Pdf\Traits\PdfStyleTrait;
use App\Traits\MathTrait;
use fpdf\PdfDocument;
use fpdf\PdfLayout;
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfTextAlignment;
use fpdf\PdfUnit;
use fpdf\PdfZoom;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract report with default header, footer, bookmarks, style and page index capabilities.
 */
abstract class AbstractReport extends PdfDocument
{
    use MathTrait;
    use PdfBookmarkTrait {
        addPageIndex as renderPageIndex;
    }
    use PdfColumnTranslatorTrait;
    use PdfStyleTrait;

    /**
     * The encoding source.
     */
    private const ENCODING_FROM = [
        'ASCII',
        'UTF-8',
        'CP1252',
        'ISO-8859-1',
    ];

    /**
     * The encoding target.
     */
    private const ENCODING_TO = 'CP1252';

    /**
     * The footer.
     */
    private readonly ReportFooter $footer;

    /**
     * The header.
     */
    private readonly ReportHeader $header;

    /**
     * The translator.
     */
    private readonly TranslatorInterface $translator;

    public function __construct(
        protected readonly AbstractController $controller,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize $size = PdfPageSize::A4
    ) {
        parent::__construct($orientation, $unit, $size);
        $this->setDisplayMode(PdfZoom::FULL_PAGE, PdfLayout::SINGLE);
        $this->setAutoPageBreak(true, $this->bottomMargin - self::LINE_HEIGHT);

        $this->translator = $this->controller->getTranslator();
        $appName = $controller->getApplicationName();
        $this->setCreator($appName);
        $userName = $controller->getUserIdentifier();
        if (null !== $userName) {
            $this->setAuthor($userName);
        }

        $service = $this->controller->getUserService();
        $this->header = new ReportHeader($this);
        $this->header->setCustomer($service->getCustomer());
        $this->footer = new ReportFooter($this);
        $this->footer->setContent($appName, $controller->getApplicationOwnerUrl());
    }

    /**
     * {@inheritdoc}
     *
     * Override the default behavior by adding the translated title.
     */
    public function addPageIndex(
        ?string $title = null,
        ?PdfStyle $titleStyle = null,
        ?PdfStyle $contentStyle = null,
        bool $addBookmark = true,
        string $separator = '.'
    ): static {
        $title ??= $this->trans('report.index');

        return $this->renderPageIndex($title, $titleStyle, $contentStyle, $addBookmark, $separator);
    }

    public function footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the footer.
     */
    public function getFooter(): ReportFooter
    {
        return $this->footer;
    }

    /**
     * Gets the header.
     */
    public function getHeader(): ReportHeader
    {
        return $this->header;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function header(): void
    {
        $this->header->output();
    }

    /**
     * Render this document.
     *
     * @return bool <code>true</code> if rendered successfully
     */
    abstract public function render(): bool;

    /**
     * Renders a single line with the header style for the given number of elements.
     *
     * @return bool <code>true</code> if the given number of elements is greater than 0
     */
    public function renderCount(PdfTable $table, \Countable|array|int $count, string $message = 'common.count'): bool
    {
        $this->resetStyle();
        $text = $this->translateCount($count, $message);
        $table->singleLine($text, PdfStyle::getHeaderStyle(), PdfTextAlignment::LEFT);

        return $count > 0;
    }

    /**
     * Sets the description to be translated.
     *
     * @param string|\Stringable|TranslatableInterface $id         the description identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     */
    public function setDescriptionTrans(string|\Stringable|TranslatableInterface $id, array $parameters = []): static
    {
        $description = $this->trans($id, $parameters);
        $this->getHeader()->setDescription($description);

        return $this;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string|\Stringable|TranslatableInterface $id         the title identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param bool                                     $isUTF8     indicates if the title is encoded in
     *                                                             ISO-8859-1 (false) or UTF-8 (true)
     */
    public function setTitleTrans(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        bool $isUTF8 = false
    ): static {
        return $this->setTitle($this->trans($id, $parameters), $isUTF8);
    }

    protected function cleanText(string $str): string
    {
        $str = parent::cleanText($str);
        if ('' === $str) {
            return $str;
        }

        try {
            return parent::convertEncoding($str, self::ENCODING_TO, self::ENCODING_FROM);
        } catch (\ValueError) {
            return $str;
        }
    }

    /**
     * Gets the translated count text.
     */
    protected function translateCount(\Countable|array|int $count, string $message = 'common.count'): string
    {
        if (!\is_int($count)) {
            $count = \count($count);
        }

        return $this->trans($message, ['%count%' => $count]);
    }
}
