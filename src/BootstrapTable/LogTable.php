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
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The application logs table.
 *
 * @author Laurent Muller
 */
class LogTable extends AbstractTable implements \Countable
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
     * {@inheritdoc}
     */
    public function count(): int
    {
        $entries = $this->service->getEntries();
        if (!\is_array($entries)) {
            return 0;
        }

        /* @var array $entries */
        return \count($entries[LogService::KEY_LOGS]);
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
        $query = parent::getDataQuery($request);
        $query->addCustomData(self::PARAM_CHANNEL, (string) $request->get(self::PARAM_CHANNEL, ''));
        $query->addCustomData(self::PARAM_LEVEL, (string) $request->get(self::PARAM_LEVEL, ''));

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
        return EntityVoterInterface::ENTITY_LOG;
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
     * {@inheritDoc}
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = new DataResults();

        $entries = $this->service->getEntries();
        if (!\is_array($entries)) {
            $results->status = Response::HTTP_PRECONDITION_FAILED;

            return $results;
        }

        // get entities
        $entities = $entries[LogService::KEY_LOGS];
        if (empty($entities)) {
            $results->status = Response::HTTP_PRECONDITION_FAILED;

            return $results;
        }

        // count
        $results->totalNotFiltered = \count($entities);

        // filter
        if ($level = $query->customData[self::PARAM_LEVEL]) {
            $entities = LogService::filterLevel($entities, $level);
        }
        if ($channel = $query->customData[self::PARAM_CHANNEL]) {
            $entities = LogService::filterChannel($entities, $channel);
        }
        if ($search = $query->search) {
            $entities = LogService::filter($entities, $search, Utils::isString($level), Utils::isString($channel));
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
        $levels = \array_keys($entries[LogService::KEY_LEVELS]);
        $channels = \array_keys($entries[LogService::KEY_CHANNELS]);

        // ajax?
        if (!$query->callback) {
            // action parameters
            $results->params = [
                self::PARAM_CHANNEL => $channel,
                self::PARAM_LEVEL => $level,
            ];

            // custom data
            $results->customData = [
                'level' => $level,
                'levels' => $levels,
                'channel' => $channel,
                'channels' => $channels,
                'file' => $this->service->getFileName(),
            ];
        }

        return $results;
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
