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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Model\LogFile;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LogLevel;

/**
 * Spreadsheet document for application logs.
 */
class LogsDocument extends AbstractDocument
{
    /**
     * The border colors for levels.
     *
     * @var array<string, string>
     */
    private array $colors = [];

    /**
     * Constructor.
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function render(): bool
    {
        $logFile = $this->logFile;

        // initialize
        $this->start('log.title', true);

        // headers
        $alignments = [Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_TOP];
        $row = $this->setHeaderValues([
            'log.fields.level' => $alignments,
            'log.fields.channel' => $alignments,
            'log.fields.createdAt' => $alignments,
            'log.fields.message' => $alignments,
            'log.fields.user' => $alignments,
        ]);

        // formats
        $this->setFormat(3, 'dd/mm/yyyy hh:mm:ss')
            ->setColumnWidth(4, 140, true);

        // logs
        $logs = $logFile->getLogs();
        $sheet = $this->getActiveSheet();
        foreach ($logs as $log) {
            $this->setRowValues($row, [
                $log->getLevel(true),
                $log->getChannel(true),
                $log->getCreatedAt(),
                $log->getMessage(),
                $log->getUser(),
            ])->setBorderStyle($sheet, $row, $log->getLevel());
            ++$row;
        }
        $this->finish();

        return true;
    }

    private function getLevelColor(string $level): string
    {
        if (!isset($this->colors[$level])) {
            return $this->colors[$level] = match ($level) {
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::EMERGENCY,
                LogLevel::ERROR => 'dc3545',
                LogLevel::WARNING => 'ffc107',
                LogLevel::DEBUG => '007bff',
                default => '17a2b8',
            };
        }

        return $this->colors[$level];
    }

    private function setBorderStyle(Worksheet $sheet, int $row, ?string $level): void
    {
        if (null !== $level && '' !== $level) {
            $sheet->getStyle("A$row")
                ->getBorders()
                ->getLeft()
                ->setBorderStyle(Border::BORDER_THICK)
                ->getColor()->setARGB($this->getLevelColor($level));
        }
    }
}
