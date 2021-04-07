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

namespace App\BootstrapTable;

use App\Interfaces\TableInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains data results.
 *
 * @author Laurent Muller
 */
class DataResults implements \JsonSerializable
{
    /**
     * The table attributes.
     */
    public array $attributes = [];

    /**
     * The card view state.
     */
    public bool $card = false;

    /**
     * The column definitions.
     *
     * @var Column[]
     */
    public array $columns = [];

    /**
     * The custom datas.
     */
    public array $customData = [];

    /**
     * The number of filtered rows.
     */
    public int $filtered = 0;

    /**
     * The selected identifier.
     */
    public int $id = 0;

    /**
     * The maximum number of results to retrieve (the "limit").
     */
    public int $limit = TableInterface::PAGE_SIZE;

    /**
     * The position of the first result to retrieve (the "offset").
     */
    public int $offset = 0;

    /**
     * The sort order ('asc' or 'desc').
     */
    public string $order = Column::SORT_ASC;

    /**
     * The page index (first = 1).
     */
    public int $page = 1;

    /**
     * The pages list.
     *
     * @var int[]
     */
    public array $pageList = TableInterface::PAGE_LIST;

    /**
     * The action parameters.
     */
    public array $params = [];

    /**
     * The rows to display.
     */
    public array $rows = [];

    /**
     * The search term.
     */
    public string $search = '';

    /**
     * The sorted field.
     */
    public string $sort = '';

    /**
     * The response status.
     */
    public int $status = Response::HTTP_OK;

    /**
     * The number of unfiltered rows.
     */
    public int $totalNotFiltered = 0;

    /**
     * Adds an attribute to this list of attributes.
     *
     * @param string $name  the attribute name
     * @param mixed  $value attribute value
     */
    public function addAttribute(string $name, $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Adds a custom data to this list of custom datas.
     *
     * @param string $name  the custom data name
     * @param mixed  $value custom data value
     */
    public function addCustomData(string $name, $value): self
    {
        $this->customData[$name] = $value;

        return $this;
    }

    /**
     * Adds a parameter to this list of paramaters.
     *
     * @param string $name  the parameter name
     * @param mixed  $value parameter value
     */
    public function addParameter(string $name, $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            TableInterface::PARAM_TOTAL_NOT_FILTERED => $this->totalNotFiltered,
            TableInterface::PARAM_TOTAL => $this->filtered,
            TableInterface::PARAM_ROWS => $this->rows,
        ];
    }
}
