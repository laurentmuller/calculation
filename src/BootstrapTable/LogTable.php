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

use App\Entity\Log;
use App\Interfaces\EntityVoterInterface;
use App\Service\LogService;
use App\Util\FormatUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;

/**
 * The application logs table.
 *
 * @author Laurent Muller
 */
class LogTable extends AbstractTable
{
    /**
     * The channel parameter name.
     */
    public const PARAM_CHANNEL = 'channel';

    /**
     * The level parameter name.
     */
    public const PARAM_LEVEL = 'level';

    /**
     * The created at column name.
     */
    private const COLUMN_DATE = 'createdAt';

    /**
     * The selected channel.
     */
    private string $channel = '';

    /**
     * The channels.
     *
     * @var string[]
     */
    private $channels;

    /**
     * The selected level.
     */
    private string $level = '';

    /**
     * The levels.
     *
     * @var string[]
     */
    private $levels;

    /**
     * The log service.
     */
    private LogService $service;

    /**
     * Constructor.
     */
    public function __construct(LogService $service)
    {
        $this->service = $service;
    }

    /**
     * Formats the channel.
     */
    public function formatChannel(string $value): string
    {
        return LogService::getChannel($value, true);
    }

    /**
     * Formats the date.
     */
    public function formatCreatedAt(\DateTimeInterface $value): string
    {
        return FormatUtils::formatDateTime($value, null, \IntlDateFormatter::MEDIUM);
    }

    /**
     * Formats the log level.
     *
     * @param string $value the source
     *
     * @return string the level
     */
    public function formatLevel(string $value): string
    {
        return LogService::getLevel($value, true);
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    /**
     * Gets the channels.
     *
     * @return string[]
     */
    public function getChannels(): array
    {
        return $this->channels ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): string
    {
        return EntityVoterInterface::ENTITY_LOG;
    }

    /**
     * Gets the file name to parse.
     */
    public function getFileName(): ?string
    {
        return $this->service->getFileName();
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * Gets the levels.
     *
     * @return string[]
     */
    public function getLevels(): array
    {
        return $this->levels ?? [];
    }

    /**
     * Gets the log service.
     */
    public function getService(): LogService
    {
        return $this->service;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request): array
    {
        //clear
        $this->clearValues();

        // get entries
        if (!$entries = $this->service->getEntries()) {
            return [];
        }

        // get entities
        $entities = $entries[LogService::KEY_LOGS];
        if (empty($entities)) {
            return [];
        }

        // count
        $totalNotFiltered = \count($entities);

        // filter channel and level
        $entities = $this->filterChannel($request, $entities);
        $entities = $this->filterLevel($request, $entities);

        // filter search
        if ($search = (string) $request->get(self::PARAM_SEARCH, '')) {
            $entities = $this->filterSearch($entities, $search);
        }
        $filtered = \count($entities);

        // sort
        [$sort, $order] = $this->getSort($request);
        if (Utils::isString($sort)) {
            $this->sort($entities, $sort, $order);
        }

        // limit
        [$offset, $limit, $page] = $this->getLimit($request);
        $entities = \array_slice($entities, $offset, $limit);

        // map entities
        $rows = $this->mapEntities($entities);

        // copy
        $this->channels = \array_keys($entries[LogService::KEY_CHANNELS]);
        $this->levels = \array_keys($entries[LogService::KEY_LEVELS]);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                'totalNotFiltered' => $totalNotFiltered,
                'total' => $filtered,
                'rows' => $rows,
            ];
        }

        // page list
        $pageList = $this->getPageList($totalNotFiltered);
        $limit = \min($limit, \max($pageList));

        // render
        return [
            'columns' => $this->getColumns(),
            'rows' => $rows,

            'card' => $this->getParamCard($request),
            'id' => $this->getParamId($request),

            'totalNotFiltered' => $totalNotFiltered,
            'total' => $filtered,

            'page' => $page,
            'limit' => $limit,
            'offset' => $offset,
            'search' => $search,
            'pageList' => $pageList,

            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/log.json';
    }

    /**
     * Clear values.
     */
    private function clearValues(): void
    {
        $this->level = '';
        $this->levels = [];
        $this->channel = '';
        $this->channels = [];
    }

    /**
     * Filters the log for the request channel (if any).
     *
     * @param Request $request  the request to get the channel
     * @param Log[]   $entities the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(Request $request, array $entities): array
    {
        if ($this->channel = $request->get(self::PARAM_CHANNEL, '')) {
            return \array_filter($entities, function (Log $entity) {
                return  $this->channel === $entity->getChannel();
            });
        }

        return $entities;
    }

    /**
     * Filters the log for the request level (if any).
     *
     * @param Request $request  the request to get the level
     * @param Log[]   $entities the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(Request $request, array $entities): array
    {
        if ($this->level = $request->get(self::PARAM_LEVEL, '')) {
            return \array_filter($entities, function (Log $entity) {
                return $this->level === $entity->getLevel();
            });
        }

        return $entities;
    }

    /**
     * Filters the logs for the given value.
     *
     * @param Log[]  $entities the logs to search in
     * @param string $value    the value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filterSearch(array $entities, string $value): array
    {
        $skipLevel = Utils::isString($this->level);
        $skipChannel = Utils::isString($this->channel);

        $filter = function (Log $log) use ($value, $skipChannel, $skipLevel) {
            if (!$skipLevel) {
                $level = $this->formatLevel($log->getLevel());
                if (Utils::contains($level, $value, true)) {
                    return true;
                }
            }

            if (!$skipChannel) {
                $channel = $this->formatChannel($log->getChannel());
                if (Utils::contains($channel, $value, true)) {
                    return true;
                }
            }

            $date = $this->formatCreatedAt($log->getCreatedAt());
            if (Utils::contains($date, $value, true)) {
                return true;
            }

            if (Utils::contains($log->getMessage(), $value, true)) {
                return true;
            }

            return false;
        };

        return \array_filter($entities, $filter);
    }

    /**
     * Gets the limit, the maximum and the page parameters.
     *
     * @param Request $request the request to get values from
     *
     * @return int[] the offset, the limit and the page parameters
     */
    private function getLimit(Request $request): array
    {
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor($this->safeDivide($offset, $limit));

        return [$offset, $limit, $page];
    }

    /**
     * Gets the sorted field and order.
     *
     * @param Request $request the request to get values from
     *
     * @return string[] the sorted field and order
     */
    private function getSort(Request $request): array
    {
        $sort = (string) $this->getRequestValue($request, self::PARAM_SORT, self::COLUMN_DATE);
        $order = (string) $this->getRequestValue($request, self::PARAM_ORDER, Column::SORT_DESC);

        return [$sort, $order];
    }

    /**
     * Sort logs.
     *
     * <b>NB:</b> Sorts only when not the default order (date ascending).
     *
     * @param Log[]  $entities  the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     */
    private function sort(array &$entities, string $field, string $direction): void
    {
        if (self::COLUMN_DATE !== $field || Column::SORT_ASC !== $direction) {
            $ascending = Column::SORT_ASC === $direction;
            Utils::sortField($entities, $field, $ascending);
        }
    }
}
