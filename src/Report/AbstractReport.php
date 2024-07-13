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
use App\Pdf\PdfDocument;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Pdf\Traits\PdfColumnTranslatorTrait;
use fpdf\PdfOrientation;
use fpdf\PdfPageSize;
use fpdf\PdfTextAlignment;
use fpdf\PdfUnit;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract report.
 */
abstract class AbstractReport extends PdfDocument
{
    use PdfColumnTranslatorTrait;

    private readonly TranslatorInterface $translator;

    public function __construct(
        protected readonly AbstractController $controller,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT,
        PdfUnit $unit = PdfUnit::MILLIMETER,
        PdfPageSize $size = PdfPageSize::A4
    ) {
        parent::__construct($orientation, $unit, $size);
        $this->translator = $this->controller->getTranslator();
        $appName = $controller->getApplicationName();
        $this->setCreator($appName);
        $userName = $controller->getUserIdentifier();
        if (null !== $userName) {
            $this->setAuthor($userName);
        }
        $service = $this->controller->getUserService();
        $this->getHeader()->setCustomer($service->getCustomer());
        $this->getFooter()->setContent($appName, $controller->getApplicationOwnerUrl());
    }

    /**
     * {@inheritdoc}
     *
     * Override the default behavior by adding a translated title if null and the page index to the bookmarks.
     */
    public function addPageIndex(
        ?string $title = null,
        ?PdfStyle $titleStyle = null,
        ?PdfStyle $contentStyle = null,
        bool $addBookmark = true,
        string $separator = '.'
    ): self {
        $title ??= $this->trans('report.index');
        parent::addPageIndex($title, $titleStyle, $contentStyle, $addBookmark, $separator);

        return $this;
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Render this document.
     *
     * @return bool <code>true</code> if rendered successfully
     */
    abstract public function render(): bool;

    /**
     * Renders a single line with the given number of elements.
     *
     * @return bool <code>true</code> if the given number of elements is greater than 0
     */
    public function renderCount(PdfTable $table, \Countable|array|int $count, string $message = 'common.count'): bool
    {
        $this->resetStyle();
        if (!\is_int($count)) {
            $count = \count($count);
        }
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
