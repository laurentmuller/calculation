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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Excel\ExcelDocument;

/**
 * Asbtract Excel document.
 *
 * @author Laurent Muller
 */
abstract class AbstractDocument extends ExcelDocument
{
    /**
     * The parent controller.
     *
     * @var AbstractController
     */
    protected $controller;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller->getTranslator());
        $this->controller = $controller;
    }

    /**
     * Render this document.
     *
     * @return bool true if rendered successfully; false otherwise
     */
    abstract public function render(): bool;

    /**
     * Ends render this document by selecting the 'A2' cell.
     */
    protected function finish(): self
    {
        $this->setSelectedCell('A2');

        return $this;
    }

    /**
     * Starts render this document.
     *
     * @param string $title     the spread sheet title to translate
     * @param bool   $landscape true to set landscape orientation, false for default (portrait)
     */
    protected function start(string $title, bool $landscape = false): self
    {
        $this->initialize($this->controller, $title, $landscape);

        return $this;
    }
}
