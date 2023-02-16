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

/**
 * Abstract report rendering an array of objects.
 *
 * @template T
 */
abstract class AbstractArrayReport extends AbstractReport
{
    /**
     * Constructor.
     *
     * @param AbstractController     $controller  the parent controller
     * @param T[]                    $entities    the entities to render
     * @param PdfDocumentOrientation $orientation the page orientation
     * @param PdfDocumentUnit        $unit        the user unit
     * @param PdfDocumentSize        $size        the document size
     *
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(
        AbstractController $controller,
        protected array $entities,
        PdfDocumentOrientation $orientation = PdfDocumentOrientation::PORTRAIT,
        PdfDocumentUnit $unit = PdfDocumentUnit::MILLIMETER,
        PdfDocumentSize $size = PdfDocumentSize::A4
    ) {
        parent::__construct($controller, $orientation, $unit, $size);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if ([] !== $this->entities) {
            return $this->doRender($this->entities);
        }

        return false;
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
