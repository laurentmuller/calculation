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
     *
     * @var array<string, string|bool|int>
     */
    public array $attributes = [];

    /**
     * The column definitions.
     *
     * @var Column[]
     */
    public array $columns = [];

    /**
     * The custom datas.
     *
     * @var array<string, mixed>
     */
    public array $customData = [];

    /**
     * The number of filtered rows.
     */
    public int $filtered = 0;

    /**
     * The pages list.
     *
     * @var int[]
     */
    public array $pageList = TableInterface::PAGE_LIST;

    /**
     * The parameters.
     *
     * @var array<string, string|bool|int|\BackedEnum>
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
     */
    public function addAttribute(string $name, string|bool|int $value): self
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Adds a custom data to this list of custom datas.
     */
    public function addCustomData(string $name, mixed $value): self
    {
        $this->customData[$name] = $value;

        return $this;
    }

    /**
     * Adds a parameter to this list of parameters.
     */
    public function addParameter(string $name, string|bool|int|\BackedEnum $value): self
    {
        $this->params[$name] = $value;

        return $this;
    }

    /**
     * Gets a custom data value for the given name.
     *
     * @psalm-api
     */
    public function getCustomData(string $name, mixed $default = null): mixed
    {
        return $this->customData[$name] ?? $default;
    }

    /**
     * Gets a parameter value for the given name.
     *
     * @psalm-return ($default is null ? (bool|string|int|\BackedEnum|null)
     * : ($default is bool ? bool
     * : ($default is string ? string
     * : ($default is int ? int
     * : \BackedEnum))))
     */
    public function getParameter(string $name, bool|string|int|\BackedEnum $default = null): bool|string|int|\BackedEnum|null
    {
        /** @psalm-var bool|string|int|\BackedEnum|null $value */
        $value = $this->params[$name] ?? $default;

        return $value;
    }

    public function jsonSerialize(): array
    {
        return [
            TableInterface::PARAM_TOTAL_NOT_FILTERED => $this->totalNotFiltered,
            TableInterface::PARAM_TOTAL => $this->filtered,
            TableInterface::PARAM_ROWS => $this->rows,
        ];
    }

    /**
     * Set the response status.
     *
     * @param int<100, 511> $status one of the Response::HTTP_* status
     */
    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }
}
