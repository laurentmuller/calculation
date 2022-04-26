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

use App\Interfaces\TableInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contains data results.
 */
class DataResults implements \JsonSerializable
{
    /**
     * The table attributes.
     */
    public array $attributes = [];

    /**
     * The column definitions.
     *
     * @var array<Column>
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
     * The pages list.
     *
     * @var array<int>
     */
    public array $pageList = TableInterface::PAGE_LIST;

    /**
     * The action parameters.
     */
    public array $params = [];

    /**
     * The rows to display.
     *
     * @var array<\App\Entity\AbstractEntity|array>
     */
    public array $rows = [];

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
    public function addAttribute(string $name, mixed $value): self
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
    public function addCustomData(string $name, mixed $value): self
    {
        $this->customData[$name] = $value;

        return $this;
    }

    /**
     * Adds a parameter to this list of parameters.
     *
     * @param string $name  the parameter name
     * @param mixed  $value parameter value
     */
    public function addParameter(string $name, mixed $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Gets a custom data value for the given name.
     *
     * @param string     $name    the custom data name to get value for
     * @param mixed|null $default the default value to return if the custom data is nof found
     *
     * @return mixed|null the custom data value, if found; the default value otherwise
     */
    public function getCustomData(string $name, mixed $default = null): mixed
    {
        return $this->customData[$name] ?? $default;
    }

    /**
     * Gets a parameter value for the given name.
     *
     * @param string     $name    the parameter name to get value for
     * @param mixed|null $default the default value to return if the parameter is nof found
     *
     * @return mixed the parameter value, if found; the default value otherwise
     */
    public function getParams(string $name, mixed $default = null): mixed
    {
        return $this->params[$name] ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize(): array
    {
        return [
            TableInterface::PARAM_TOTAL_NOT_FILTERED => $this->totalNotFiltered,
            TableInterface::PARAM_TOTAL => $this->filtered,
            TableInterface::PARAM_ROWS => $this->rows,
        ];
    }
}
