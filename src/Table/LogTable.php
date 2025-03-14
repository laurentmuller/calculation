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
use App\Model\LogChannel;
use App\Model\LogFile;
use App\Model\LogLevel;
use App\Service\LogService;
use App\Utils\FileUtils;
use App\Utils\LogFilter;
use App\Utils\LogSorter;
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

    public function __construct(private readonly LogService $service, private readonly Environment $twig)
    {
    }

    #[\Override]
    public function count(): int
    {
        return $this->service->getLogFile()?->count() ?? 0;
    }

    /**
     * @throws \Twig\Error\Error
     *
     * @psalm-api
     */
    public function formatChannel(string $value, Log $log): string
    {
        return $this->twig->render('macros/_cell_log_channel.html.twig', ['value' => $value, 'log' => $log]);
    }

    /**
     * Formats the date.
     *
     * @psalm-api
     */
    public function formatCreatedAt(\DateTimeInterface $value): string
    {
        return Log::formatDate($value);
    }

    /**
     * @throws \Twig\Error\Error
     *
     * @psalm-api
     */
    public function formatLevel(string $value, Log $log): string
    {
        return $this->twig->render('macros/_cell_log_level.html.twig', ['value' => $value, 'log' => $log]);
    }

    #[\Override]
    public function getEmptyMessage(): ?string
    {
        return 0 === $this->count() ? 'log.list.empty' : null;
    }

    #[\Override]
    public function getEntityClassName(): ?string
    {
        return Log::class;
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'log.json');
    }

    #[\Override]
    protected function handleQuery(DataQuery $query): DataResults
    {
        $results = parent::handleQuery($query);
        $logFile = $this->service->getLogFile();
        if (!$logFile instanceof LogFile || $logFile->isEmpty()) {
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
            $level = $this->getQueryLevel($query);
            $channel = $this->getQueryChannel($query);
            $results->params = [
                self::PARAM_LEVEL => $level,
                self::PARAM_CHANNEL => $channel,
            ];
            $results->customData = [
                'level' => $level,
                'channel' => $channel,
                'levels' => $this->mapLevels($logFile->getLevels()),
                'channels' => $this->mapChannels($logFile->getChannels()),
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
        $level = $this->getQueryLevel($query);
        $channel = $this->getQueryChannel($query);
        if (LogFilter::isFilter($search, $level, $channel)) {
            $filter = new LogFilter($search, $level, $channel);

            return $filter->filter($entities);
        }

        return $entities;
    }

    private function getQueryChannel(DataQuery $query): string
    {
        return $query->getStringParameter(self::PARAM_CHANNEL);
    }

    private function getQueryLevel(DataQuery $query): string
    {
        return $query->getStringParameter(self::PARAM_LEVEL);
    }

    /**
     * @param array<string, LogChannel> $channels
     */
    private function mapChannels(array $channels): array
    {
        foreach ($channels as &$channel) {
            $channel = $this->replaceIcon($channel->getChannelIcon());
        }

        return $channels;
    }

    /**
     * @psalm-param array<string, LogLevel> $levels
     */
    private function mapLevels(array $levels): array
    {
        foreach ($levels as &$level) {
            $level = $this->replaceIcon($level->getLevelIcon()) . ' ' . $level->getLevelColor();
        }

        return $levels;
    }

    private function replaceIcon(string $icon): string
    {
        return \str_replace('fa-fw fa-solid fa-', '', $icon) . ' fa-fw';
    }

    /**
     * Sort logs.
     *
     * @param Log[] $entities
     */
    private function sort(DataQuery $query, array &$entities): void
    {
        $field = $query->sort;
        $ascending = self::SORT_ASC === $query->order;
        if ('' !== $field && !LogSorter::isDefaultSort($field, $ascending)) {
            $sorter = new LogSorter($field, $ascending);
            $sorter->sort($entities);
        }
    }
}
