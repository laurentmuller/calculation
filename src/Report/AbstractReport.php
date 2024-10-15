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
use App\Pdf\Traits\PdfColumnTranslatorTrait;
use App\Pdf\Traits\PdfStyleTrait;
use App\Traits\MathTrait;
use fpdf\Enums\PdfLayout;
use fpdf\Enums\PdfOrientation;
use fpdf\Enums\PdfTextAlignment;
use fpdf\Enums\PdfZoom;
use fpdf\PdfDocument;
use fpdf\Traits\PdfBookmarkTrait;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract report with a header and footer; styles and bookmark capabilities.
 */
abstract class AbstractReport extends PdfDocument
{
    use MathTrait;
    use PdfBookmarkTrait;
    use PdfColumnTranslatorTrait;
    use PdfStyleTrait;

    /** The encoding source. */
    private const ENCODING_FROM = [
        'ASCII',
        'UTF-8',
        'CP1252',
        'ISO-8859-1',
    ];

    /** The encoding target. */
    private const ENCODING_TO = 'CP1252';

    private readonly ReportFooter $footer;
    private readonly ReportHeader $header;
    private readonly TranslatorInterface $translator;

    /**
     * Create a new instance.
     *
     * @param AbstractController $controller  the controller to get services from
     * @param PdfOrientation     $orientation the page orientation
     */
    public function __construct(
        AbstractController $controller,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT
    ) {
        parent::__construct($orientation);
        $this->setAutoPageBreak(true, $this->bottomMargin - self::LINE_HEIGHT)
            ->setLayout(PdfLayout::SINGLE_PAGE)
            ->setZoom(PdfZoom::FULL_PAGE);

        $this->translator = $controller->getTranslator();
        $this->header = new ReportHeader($this);
        $this->footer = new ReportFooter($this);

        $name = $controller->getApplicationName();
        $this->setCreator($name);
        $user = $controller->getUserIdentifier();
        if (null !== $user) {
            $this->setAuthor($user);
        }

        $this->header->setCustomer($controller->getUserService()->getCustomer());
        $this->footer->setContent($name, $controller->getApplicationOwnerUrl());
    }

    /**
     * This implementation output the report footer.
     */
    public function footer(): void
    {
        $this->footer->output();
    }

    /**
     * Gets the report footer.
     */
    public function getFooter(): ReportFooter
    {
        return $this->footer;
    }

    /**
     * Gets the report header.
     */
    public function getHeader(): ReportHeader
    {
        return $this->header;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * This implementation output the report header.
     */
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
