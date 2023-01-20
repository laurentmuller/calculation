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
use App\Service\LogService;
use App\Util\FileUtils;
use App\Util\Utils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

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
    public function __construct(private readonly LogService $service, private readonly Environment $twig)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function count(): int
    {
        return $this->service->getLogFile()?->count() ?? 0;
    }

    /**
     * Formats the channel.
     *
     * @throws \Twig\Error\Error
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function formatChannel(string $value, Log $log): string
    {
        return $this->twig->render('macros/_cell_log_channel.html.twig', ['log' => $log]);
    }

    /**
     * Formats the date.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function formatCreatedAt(\DateTimeInterface $value, Log $log): string
    {
        return $log->getFormattedDate();
    }

    /**
     * Format the level.
     *
     * @throws \Twig\Error\Error
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function formatLevel(string $value, Log $log): string
    {
        return $this->twig->render('macros/_cell_log_level.html.twig', ['log' => $log]);
    }

    /**
     * {@inheritDoc}
     */
    public function getDataQuery(Request $request): DataQuery
    {
        $level = (string) $this->getRequestString($request, self::PARAM_LEVEL, '');
        $channel = (string) $this->getRequestString($request, self::PARAM_CHANNEL, '');

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
     * {@inheritDoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);

        if (null === $logFile = $this->service->getLogFile()) {
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
            $entities = $this->filterLevel($entities, (string) $level);
        }
        /** @var string|null $channel */
        $channel = $query->customData[self::PARAM_CHANNEL];
        if ($isChannel = Utils::isString($channel)) {
            $entities = $this->filterChannel($entities, (string) $channel);
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
     * Filters the log for the given channel.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterChannel(array $logs, string $value): array
    {
        return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, $log->getChannel()));
    }

    /**
     * Filters the log for the given level.
     *
     * @param Log[] $logs the logs to search in
     *
     * @return Log[] the filtered logs
     */
    private function filterLevel(array $logs, string $value): array
    {
        return \array_filter($logs, static fn (Log $log): bool => 0 === \strcasecmp($value, $log->getLevel()));
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
        if (self::COLUMN_DATE === $field && self::SORT_DESC === $direction) {
            return;
        }

        // date? (single sort)
        if (self::COLUMN_DATE === $field) {
            \uasort($entities, fn (Log $a, Log $b): int => $a->getCreatedAt() <=> $b->getCreatedAt());

            return;
        }

        // multiple-sort
        $fields = [
                $field => self::SORT_ASC === $direction,
                self::COLUMN_DATE => false,
            ];
        Utils::sortFields($entities, $fields);
    }
}
