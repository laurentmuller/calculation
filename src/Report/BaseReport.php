<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\BaseController;
use App\Pdf\PdfDocument;
use App\Traits\FormatterTrait;
use App\Traits\TranslatorTrait;
use App\Twig\FormatExtension;

/**
 * Abstract report.
 *
 * @author Laurent Muller
 */
abstract class BaseReport extends PdfDocument
{
    use FormatterTrait;
    use TranslatorTrait;

    /**
     * The Twig extension to format values.
     *
     * @var FormatExtension
     */
    private $extension;

    /**
     * Constructor.
     *
     * @param BaseController $controller  the parent controller
     * @param string         $orientation the page orientation. One of the ORIENTATION_XX contants.
     * @param string         $unit        the measure unit. One of the UNIT_XX contants.
     * @param mixed          $size        the document size. One of the SIZE_XX contants or an array containing the width and height of the document.
     */
    public function __construct(BaseController $controller, string $orientation = self::ORIENTATION_PORTRAIT, string $unit = self::UNIT_MILLIMETER, $size = self::SIZE_A4)
    {
        parent::__construct($orientation, $unit, $size);

        $this->translator = $controller->getTranslator();
        $this->application = $controller->getApplication();

        $appName = $controller->getApplicationName();
        $this->SetCreator($appName);
        $this->setApplicationName($appName)
            ->setOwnerUrl($controller->getApplicationOwnerUrl())
            ->setCompany($this->application->getCustomerName())
            ->setCompanyUrl($this->application->getCustomerUrl());

        $userName = $controller->getUserName();
        if (null !== $userName) {
            $this->SetAuthor($userName);
        }
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
            $this->extension = new FormatExtension($this->translator, $this->application);
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
        $text = $this->trans('common.count', [
            '%count%' => $count,
        ]);

        $margins = $this->setCellMargin(0);
        $this->Cell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::MOVE_TO_NEW_LINE, $align);
        $this->setCellMargin($margins);

        return $count > 0;
    }

    /**
     * Sets the title (to be translated).
     *
     * @param string $id         the title id (may also be an object that can be cast to string)
     * @param array  $parameters an array of parameters for the title
     * @param bool   $isUTF8     true to encode to UTF-8
     */
    public function setTitleTrans(string $id, array $parameters = [], $isUTF8 = false): self
    {
        $title = $this->trans($id, $parameters);
        $this->SetTitle($title, $isUTF8);

        return $this;
    }
}
