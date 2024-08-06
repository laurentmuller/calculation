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
use fpdf\PdfOrientation;

/**
 * Abstract report rendering an array of objects.
 *
 * @template T
 */
abstract class AbstractArrayReport extends AbstractReport
{
    /**
     * @param T[] $entities the entities to render
     */
    public function __construct(
        AbstractController $controller,
        protected array $entities,
        PdfOrientation $orientation = PdfOrientation::PORTRAIT
    ) {
        parent::__construct($controller, $orientation);
    }

    public function render(): bool
    {
        return [] !== $this->entities && $this->doRender($this->entities);
    }

    /**
     * Render the given entities.
     *
     * @param T[] $entities the entities to render
     *
     * @return bool true if rendered successfully; false otherwise
     */
    abstract protected function doRender(array $entities): bool;
}
