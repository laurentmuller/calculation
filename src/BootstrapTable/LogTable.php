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
 * @author Laurent Muller
 */
class LogTable extends AbstractBootstrapTable
{
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
        $this->levels = [];
        $this->channels = [];
        $this->level = $this->channel = '';

        // get entries
        if (!$entries = $this->service->getEntries()) {
            return [];
        }

        // get entities
        $entities = $entries[LogService::KEY_LOGS];
        if (empty($entities)) {
            return [];
        }

        $totalNotFiltered = $filtered = \count($entities);

        // filter
        $skipChannel = false;
        if ($this->channel = $request->get('channel', '')) {
            $entities = $this->filterChannel($entities, $this->channel);
            $skipChannel = true;
        }
        $skipLevel = false;
        if ($this->level = $request->get('level', '')) {
            $entities = $this->filterLevel($entities, $this->level);
            $skipLevel = true;
        }

        // search
        $search = (string) $request->get(self::PARAM_SEARCH, '');
        if (Utils::isString($search)) {
            $entities = $this->filter($entities, $search, $skipChannel, $skipLevel);
        }
        $filtered = \count($entities);

        // sort
        $sort = (string) $this->getRequestValue($request, self::PARAM_SORT, self::COLUMN_DATE);
        $order = (string) $this->getRequestValue($request, self::PARAM_ORDER, BootstrapColumn::SORT_DESC);
        if (Utils::isString($sort)) {
            $this->sort($entities, $sort, $order);
        }

        // limit
        $offset = (int) $request->get(self::PARAM_OFFSET, 0);
        $limit = (int) $this->getRequestValue($request, self::PARAM_LIMIT, self::PAGE_SIZE);
        $page = 1 + (int) \floor($this->safeDivide($offset, $limit));

        // get result and map entities
        $entities = \array_slice($entities, $offset, $limit);
        $rows = empty($entities) ? [] : $this->mapEntities($entities);

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

            'sort' => $sort,
            'order' => $order,
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/log.json';

        return $this->deserializeColumns($path);
    }

    /**
     * Filters the log.
     *
     * @param Log[]  $logs        the logs to search in
     * @param string $value       the value to search for
     * @param bool   $skipChannel true to skip search in channel
     * @param bool   $skipLevel   true to skip search in level
     *
     * @return Log[] the filtered logs
     */
    private function filter(array $logs, ?string $value, bool $skipChannel, bool $skipLevel): array
    {
        if (Utils::isString($value)) {
            $filter = function (Log $log) use ($value, $skipChannel, $skipLevel) {
                if (!$skipChannel) {
                    $channel = $this->formatChannel($log->getChannel());
                    if (Utils::contains($channel, $value, true)) {
                        return true;
                    }
                }

                if (!$skipLevel) {
                    $level = $this->formatLevel($log->getLevel());
                    if (Utils::contains($level, $value, true)) {
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

            return \array_filter($logs, $filter);
        }

        return $logs;
    }

    /**
     * Filters the log for the given channel.
     *
     * @param Log[]  $logs  the logs to search in
     * @param string $value the channel value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, function (Log $log) use ($value) {
                return $value === $log->getChannel();
            });
        }

        return $logs;
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[]  $logs  the logs to search in
     * @param string $value the level value to search for
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $logs, ?string $value): array
    {
        if (Utils::isString($value)) {
            return \array_filter($logs, function (Log $log) use ($value) {
                return $value === $log->getLevel();
            });
        }

        return $logs;
    }

    /**
     * Sort logs.
     *
     * <b>NB:</b> Sorts only when not the default order (date ascending).
     *
     * @param Log[]  $logs      the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     */
    private function sort(array &$logs, string $field, string $direction): void
    {
        if (self::COLUMN_DATE !== $field || BootstrapColumn::SORT_ASC !== $direction) {
            $ascending = BootstrapColumn::SORT_ASC === $direction;
            Utils::sortField($logs, $field, $ascending);
        }
    }
}
