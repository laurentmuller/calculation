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
use App\Model\LogFile;
use App\Service\LogService;
use App\Utils\FileUtils;
use App\Utils\LogFilter;
use App\Utils\LogSorter;
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
     * Constructor.
     */
    public function __construct(private readonly LogService $service, private readonly Environment $twig)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->service->getLogFile()?->count() ?? 0;
    }

    /**
     * Formats the channel.
     *
     * @throws \Twig\Error\Error
     */
    public function formatChannel(string $value, Log $log): string
    {
        return $this->twig->render('macros/_cell_log_channel.html.twig', ['log' => $log]);
    }

    /**
     * Formats the date.
     */
    public function formatCreatedAt(\DateTimeInterface $value, Log $log): string
    {
        return $log->getFormattedDate();
    }

    /**
     * Format the level.
     *
     * @throws \Twig\Error\Error
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
        $level = $this->getRequestString($request, self::PARAM_LEVEL, '');
        $channel = $this->getRequestString($request, self::PARAM_CHANNEL, '');

        return parent::getDataQuery($request)
            ->addCustomData(self::PARAM_CHANNEL, $channel)
            ->addCustomData(self::PARAM_LEVEL, $level);
    }

    /**
     * {@inheritDoc}
     */
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'log.list.empty' : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClassName(): ?string
    {
        return Log::class;
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
     */
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        if (!($logFile = $this->service->getLogFile()) instanceof LogFile) {
            return $results->setStatus(Response::HTTP_PRECONDITION_FAILED);
        }
        if ($logFile->isEmpty()) {
            return $results->setStatus(Response::HTTP_PRECONDITION_FAILED);
        }
        $entities = $logFile->getLogs();
        $results->totalNotFiltered = \count($entities);
        $entities = $this->filter($query, $entities);
        $results->filtered = \count($entities);
        $this->sort($query, $entities);
        $entities = \array_slice($entities, $query->offset, $query->limit);
        $results->rows = $this->mapEntities($entities);
        if (!$query->callback) {
            $level = (string) $query->customData[self::PARAM_LEVEL];
            $channel = (string) $query->customData[self::PARAM_CHANNEL];
            $results->params = [
                self::PARAM_LEVEL => $level,
                self::PARAM_CHANNEL => $channel,
            ];
            $results->customData = [
                'level' => $level,
                'channel' => $channel,
                'levels' => \array_keys($logFile->getLevels()),
                'channels' => \array_keys($logFile->getChannels()),
                'file' => $logFile->getFile(),
            ];
        }

        return $results;
    }

    /**
     * Filter logs.
     *
     * @param Log[] $entities
     *
     * @return Log[]
     */
    private function filter(DataQuery $query, array $entities): array
    {
        $search = $query->search;
        $level = (string) $query->customData[self::PARAM_LEVEL];
        $channel = (string) $query->customData[self::PARAM_CHANNEL];
        if (LogFilter::isFilter($search, $level, $channel)) {
            $filter = new LogFilter($search, $level, $channel);

            return $filter->apply($entities);
        }

        return $entities;
    }

    /**
     * Sort logs.
     *
     * @param Log[] $entities
     */
    private function sort(DataQuery $query, array &$entities): void
    {
        $sort = $query->sort;
        $ascending = self::SORT_ASC === $query->order;
        if (!empty($sort) && !LogSorter::isDefaultSort($sort, $ascending)) {
            $sorter = new LogSorter($sort, $ascending);
            $sorter->sort($entities);
        }
    }
}
