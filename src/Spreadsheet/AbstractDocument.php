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

namespace App\Spreadsheet;

use App\Controller\AbstractController;

/**
 * Abstract Spreadsheet document.
 *
 * @author Laurent Muller
 */
abstract class AbstractDocument extends SpreadsheetDocument
{
    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(protected AbstractController $controller)
    {
        parent::__construct($controller->getTranslator());
    }

    /**
     * Render this document.
     *
     * @return bool true if rendered successfully; false otherwise
     */
    abstract public function render(): bool;

    /**
     * Ends render this document by selecting the given cell.
     *
     * @param string $selection the cell to select
     */
    protected function finish(string $selection = 'A2'): self
    {
        $this->setSelectedCell($selection);

        return $this;
    }

    /**
     * Starts render this document.
     *
     * @param string $title     the spreadsheet title to translate
     * @param bool   $landscape true to set landscape orientation, false for default (portrait)
     */
    protected function start(string $title, bool $landscape = false): self
    {
        $this->initialize($this->controller, $title, $landscape);

        return $this;
    }
}
