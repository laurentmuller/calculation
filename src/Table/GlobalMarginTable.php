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

namespace App\Table;

use App\Repository\GlobalMarginRepository;
use App\Util\FileUtils;

/**
 * The global margins table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<\App\Entity\GlobalMargin>
 */
class GlobalMarginTable extends AbstractEntityTable
{
    /**
     * Constructor.
     */
    public function __construct(GlobalMarginRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'global_margin.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['minimum' => self::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('search', \json_encode(false));
        }
    }
}
