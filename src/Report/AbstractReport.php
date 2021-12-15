<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfDocument;
use App\Traits\TranslatorTrait;
use App\Twig\FormatExtension;
use App\Util\FormatUtils;

/**
 * Abstract report.
 *
 * @author Laurent Muller
 */
abstract class AbstractReport extends PdfDocument
{
    use TranslatorTrait;

    /**
     * The parent controller.
     */
    protected AbstractController $controller;

    /**
     * The Twig extension to format values.
     */
    private ?FormatExtension $extension = null;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param string             $orientation the page orientation. One of the ORIENTATION_XX contants.
     * @param string             $unit        the measure unit. One of the UNIT_XX contants.
     * @param mixed              $size        the document size. One of the SIZE_XX contants or an array containing the width and height of the document.
     */
    public function __construct(AbstractController $controller, string $orientation = self::ORIENTATION_PORTRAIT, string $unit = self::UNIT_MILLIMETER, $size = self::SIZE_A4)
    {
        parent::__construct($orientation, $unit, $size);

        $this->controller = $controller;
        $this->translator = $controller->getTranslator();

        $application = $controller->getApplication();
        $appName = $controller->getApplicationName();

        // meta-data
        $this->SetCreator($appName);
        if (null !== $userName = $controller->getUserName()) {
            $this->SetAuthor($userName);
        }

        // header
        $this->header->setCustomer($application->getCustomer());

        // footer
        $this->footer->setContent($appName, $controller->getApplicationOwnerUrl());
    }

    /**
     * Filter to format a boolean value.
     *
     * @param bool   $value     the value to format
     * @param string $true      the text to use when the value is <b>TRUE</b> or <code>null</code> to use default
     * @param string $false     the text to use when the value is <b>FALSE</b> or <code>null</code> to use default
     * @param bool   $translate <code>TRUE</code> to translate texts
     */
    public function booleanFilter($value, ?string $true = null, ?string $false = null, bool $translate = false): string
    {
        return $this->getExtension()->booleanFilter($value, $true, $false, $translate);
    }

    /**
     * Gets the filter extension used to format values.
     */
    public function getExtension(): FormatExtension
    {
        if (null === $this->extension) {
            $this->extension = new FormatExtension($this->translator);
        }

        return $this->extension;
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
     * @param int|array|\Countable $count      the number of elements, an array or a \Countable object
     * @param string               $align      the text alignment
     * @param bool                 $resetStyle true to reset style before output the line
     *
     * @return bool true if the number of elements is greather than 0
     */
    public function renderCount($count, string $align = self::ALIGN_LEFT, bool $resetStyle = true): bool
    {
        // reset
        if ($resetStyle) {
            $this->resetStyle();
        }

        // count and translate
        if (\is_array($count) || $count instanceof \Countable) {
            $count = \count($count);
        }
        $text = $this->translateCount($count);

        $margins = $this->setCellMargin(0);
        $this->Cell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, $align);
        $this->setCellMargin($margins);

        return $count > 0;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string $id     the title id (may also be an object that can be cast to string)
     * @param bool   $isUTF8 true to encode to UTF-8
     */
    public function setTitleTrans(string $id, array $parameters = [], $isUTF8 = false, ?string $domain = null, ?string $locale = null): self
    {
        $title = $this->trans($id, $parameters, $domain, $locale);
        $this->SetTitle($title, $isUTF8);

        return $this;
    }

    /**
     * Gets the translated count label.
     *
     * @param int $count the number of elements
     *
     * @return string the label
     */
    protected function translateCount(int $count): string
    {
        return $this->trans('common.count', ['%count%' => FormatUtils::formatInt($count)]);
    }
}
