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

namespace App\Traits;

use Twig\Environment;

/**
 * Trait to render a table cell with a link to a filtered table.
 *
 * @property Environment $twig
 */
trait TableCellTrait
{
    /**
     * Render the link cell template.
     *
     * @psalm-param array{id: int} $entity
     *
     * @throws \Twig\Error\Error
     */
    protected function renderCell(
        int $value,
        array $entity,
        string $title,
        string|false $route,
        string $parameter
    ): string {
        $context = [
            'count' => $value,
            'title' => $title,
            'route' => $route,
            'parameters' => [
                $parameter => $entity['id'],
            ],
        ];

        return $this->twig->render('macros/_cell_table_link.html.twig', $context);
    }
}
