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

use App\Entity\Log;
use App\Interfaces\SortModeInterface;
use App\Service\LogService;
use App\Util\FileUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The application logs table.
 */
class LogTable extends AbstractTable implements \Countable
{
    /**
     * The channel parameter name.
     */
    final public const PARAM_CHANNEL = 'channel';

    /**
     * The level parameter name.
     */
    final public const PARAM_LEVEL = 'level';

    /**
     * The created at column name.
     */
    private const COLUMN_DATE = 'createdAt';

    /**
     * Constructor.
     */
    public function __construct(private readonly LogService $service)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function count(): int
    {
        if (false === $logFile = $this->service->getLogFile()) {
            return 0;
        }

        return $logFile->count();
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
        return LogService::getCreatedAt($value);
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
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $level = (string) $this->getRequestValue($request, self::PARAM_LEVEL, '', false);
        $channel = (string) $this->getRequestValue($request, self::PARAM_CHANNEL, '', false);

        $query = parent::getDataQuery($request);
        $query->addCustomData(self::PARAM_CHANNEL, $channel);
        $query->addCustomData(self::PARAM_LEVEL, $level);

        return $query;
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyMessage(): string
    {
        return 'log.list.empty';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): ?string
    {
        return Log::class;
    }

    /**
     * {@inheritDoc}
     */
    public function isEmptyAllowed(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'log.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return [self::COLUMN_DATE => SortModeInterface::SORT_DESC];
    }

    /**
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);

        if (false === $logFile = $this->service->getLogFile()) {
            $results->status = Response::HTTP_PRECONDITION_FAILED;

            return $results;
        }

        if ($logFile->isEmpty()) {
            $results->status = Response::HTTP_PRECONDITION_FAILED;

            return $results;
        }

        $entities = $logFile->getLogs();
        $results->totalNotFiltered = $logFile->count();

        // filter
        /** @var string|null $level */
        $level = $query->customData[self::PARAM_LEVEL];
        if ($isLevel = Utils::isString($level)) {
            $entities = LogService::filterLevel($entities, $level);
        }
        /** @var string|null $channel */
        $channel = $query->customData[self::PARAM_CHANNEL];
        if ($isChannel = Utils::isString($channel)) {
            $entities = LogService::filterChannel($entities, $channel);
        }
        $search = $query->search;
        if (Utils::isString($search)) {
            $entities = LogService::filter($entities, $search, $isLevel, $isChannel);
        }
        $results->filtered = \count($entities);

        // sort
        $sort = $query->sort;
        $order = $query->order;
        if (Utils::isString($sort)) {
            $this->sort($entities, $sort, $order);
        }

        // limit
        $entities = \array_slice($entities, $query->offset, $query->limit);

        // map entities
        $results->rows = $this->mapEntities($entities);

        // copy
        $levels = \array_keys($logFile->getLevels());
        $channels = \array_keys($logFile->getChannels());

        // ajax?
        if (!$query->callback) {
            // action parameters
            $results->params = [
                self::PARAM_LEVEL => $level,
                self::PARAM_CHANNEL => $channel,
            ];

            // custom data
            $results->customData = [
                'level' => $level,
                'levels' => $levels,
                'channel' => $channel,
                'channels' => $channels,
                'file' => $logFile->getFile(),
            ];
        }

        return $results;
    }

    /**
     * Sort logs.
     *
     * @param Log[]  $entities  the logs to sort
     * @param string $field     the sorted field
     * @param string $direction the sorted direction ('asc' or 'desc')
     *
     * @psalm-suppress ReferenceConstraintViolation
     */
    private function sort(array &$entities, string $field, string $direction): void
    {
        // default sorting?
        if (self::COLUMN_DATE === $field && self::SORT_ASC === $direction) {
            return;
        }

        // date? (single sort)
        if (self::COLUMN_DATE === $field) {
            $order = self::SORT_ASC === $direction ? 1 : -1;
            \usort($entities, function (Log $a, Log $b) use ($order): int {
                $dateA = $a->getCreatedAt()?->getTimestamp() ?? 0;
                $dateB = $b->getCreatedAt()?->getTimestamp() ?? 0;

                return $order * ($dateA <=> $dateB);
            });

            return;
        }

        // multiple-sort
        $fields = [
                $field => SortModeInterface::SORT_ASC === $direction,
                self::COLUMN_DATE => false,
            ];
        Utils::sortFields($entities, $fields);
    }
}
