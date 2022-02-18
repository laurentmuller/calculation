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
 * Asbtract Spreadsheet document to render an array of objects.
 *
 * @author Laurent Muller
 *
 * @template T
 */
abstract class AbstractArrayDocument extends AbstractDocument
{
    /**
     * The entities to output.
     *
     * @pslam-var T[]
     */
    protected array $entities;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param array              $entities   the entities to render
     * @psalm-param T[] $entities
     */
    public function __construct(AbstractController $controller, array $entities)
    {
        parent::__construct($controller);
        $this->entities = $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if (!empty($this->entities)) {
            return $this->doRender($this->entities);
        }

        return false;
    }

    /**
     * Render the given entities.
     *
     * @param array $entities the entities to render
     *
     * @return bool true if rendered successfully; false otherwise
     *
     * @psalm-param T[] $entities
     */
    abstract protected function doRender(array $entities): bool;
}
