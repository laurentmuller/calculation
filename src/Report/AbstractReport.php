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
use fpdf\PdfTextAlignment;
use fpdf\PdfZoom;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract report with a default header, a default footer, bookmarks, styles and bookmarks.
 */
abstract class AbstractReport extends PdfDocument
{
    use MathTrait;
    use PdfBookmarkTrait;
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

    private readonly ReportFooter $footer;
    private readonly ReportHeader $header;
    private readonly TranslatorInterface $translator;

    public function __construct(
        AbstractController $controller,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT
    ) {
        parent::__construct($orientation);
        $this->setDisplayMode(PdfZoom::FULL_PAGE, PdfLayout::SINGLE)
            ->setAutoPageBreak(true, $this->bottomMargin - self::LINE_HEIGHT);

        $this->translator = $controller->getTranslator();
        $this->header = new ReportHeader($this);
        $this->footer = new ReportFooter($this);

        $name = $controller->getApplicationName();
        $this->setCreator($name);
        $userName = $controller->getUserIdentifier();
        if (null !== $userName) {
            $this->setAuthor($userName);
        }

        $this->header->setCustomer($controller->getUserService()->getCustomer());
        $this->footer->setContent($name, $controller->getApplicationOwnerUrl());
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

        return parent::convertEncoding($str, self::ENCODING_TO, self::ENCODING_FROM);
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
