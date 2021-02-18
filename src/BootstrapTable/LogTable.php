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

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): ?string
    {
        return EntityVoterInterface::ENTITY_LOG;
    }

    /**
     * {@inheritdoc}
     */
    public function handleRequest(Request $request): array
    {
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

        // filter level
        if ($level = (string) $request->get(self::PARAM_LEVEL, '')) {
            $entities = $this->filterLevel($entities, $level);
        }

        // filter channel
        if ($channel = (string) $request->get(self::PARAM_CHANNEL, '')) {
            $entities = $this->filterChannel($entities, $channel);
        }

        // filter search
        if ($search = (string) $request->get(self::PARAM_SEARCH, '')) {
            $entities = $this->filterSearch($entities, $search, Utils::isString($level), Utils::isString($channel));
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
        $levels = \array_keys($entries[LogService::KEY_LEVELS]);
        $channels = \array_keys($entries[LogService::KEY_CHANNELS]);

        // ajax?
        if ($request->isXmlHttpRequest()) {
            return [
                self::PARAM_TOTAL_NOT_FILTERED => $totalNotFiltered,
                self::PARAM_TOTAL => $filtered,
                self::PARAM_ROWS => $rows,
            ];
        }

        // page list
        $pageList = $this->getAllowedPageList($totalNotFiltered);
        $limit = \min($limit, \max($pageList));

        // card view
        $card = $this->getParamCard($request);

        // render
        return [
            // template parameters
            self::PARAM_COLUMNS => $this->getColumns(),
            self::PARAM_ROWS => $rows,
            self::PARAM_PAGE_LIST => $pageList,
            self::PARAM_LIMIT => $limit,

            // custom parameters
            'level' => $level,
            'levels' => $levels,
            'channel' => $channel,
            'channels' => $channels,
            'file' => $this->service->getFileName(),

            // action parameters
            'params' => [
                self::PARAM_ID => $this->getParamId($request),
                self::PARAM_SEARCH => $search,
                self::PARAM_SORT => $sort,
                self::PARAM_ORDER => $order,
                self::PARAM_OFFSET => $offset,
                self::PARAM_LIMIT => $limit,
                self::PARAM_CARD => $card,
                self::PARAM_CHANNEL => $channel,
                self::PARAM_LEVEL => $level,
            ],

            // table attributes
            'attributes' => [
                'total-not-filtered' => $totalNotFiltered,
                'total-rows' => $filtered,

                'search' => \json_encode(true),
                'search-text' => $search,

                'page-list' => $this->implodePageList($pageList),
                'page-size' => $limit,
                'page-number' => $page,

                'card-view' => \json_encode($this->getParamCard($request)),

                'sort-name' => $sort,
                'sort-order' => $order,
            ],
        ];
    }

    /**
     * Returns if the log service is empty.
     *
     * @return bool true if empty
     */
    public function isEmpty(): bool
    {
        if (!$entries = $this->service->getEntries()) {
            return true;
        }

        return empty($entries[LogService::KEY_LOGS]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/log.json';
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [self::COLUMN_DATE => Column::SORT_DESC];
    }

    /**
     * Filters the log for the request channel.
     *
     * @param Log[]  $entities the logs to search in
     * @param string $channel  the selected channel
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $entities, string $channel): array
    {
        return \array_filter($entities, function (Log $entity) use ($channel) {
            return $channel === $entity->getChannel();
        });
    }

    /**
     * Filters the log for the request level.
     *
     * @param Log[]  $entities the logs to search in
     * @param string $level    the selected level
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $entities, string $level): array
    {
        return \array_filter($entities, function (Log $entity) use ($level) {
            return $level === $entity->getLevel();
        });
    }

    /**
     * Filters the logs for the given value.
     *
     * @param Log[]  $entities    the logs to search in
     * @param string $value       the value to search for
     * @param bool   $skipLevel   true to skip search in the level
     * @param bool   $skipChannel true to skip search in the channel
     *
     * @return Log[] the filtered logs
     */
    private function filterSearch(array $entities, string $value, bool $skipLevel, bool $skipChannel): array
    {
        $filter = function (Log $log) use ($value, $skipLevel, $skipChannel) {
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
