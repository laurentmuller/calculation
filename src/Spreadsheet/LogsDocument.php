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
use App\Pdf\Html\HtmlBootstrapColors;
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
     */
    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller);
        $description = $this->trans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
        $this->setDescription($description);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $logFile = $this->logFile;
        $this->start('log.title', true);
        $alignments = [Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_TOP];
        $row = $this->setHeaderValues([
            'log.fields.level' => $alignments,
            'log.fields.channel' => $alignments,
            'log.fields.createdAt' => $alignments,
            'log.fields.message' => $alignments,
            'log.fields.user' => $alignments,
        ]);
        $this->setFormat(3, 'dd/mm/yyyy hh:mm:ss')
            ->setColumnWidth(4, 140, true);
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
                LogLevel::ERROR => HtmlBootstrapColors::DANGER->getPhpOfficeColor(),
                LogLevel::WARNING => HtmlBootstrapColors::WARNING->getPhpOfficeColor(),
                LogLevel::DEBUG => HtmlBootstrapColors::SECONDARY->getPhpOfficeColor(),
                // info, notice
                default => HtmlBootstrapColors::INFO->getPhpOfficeColor(),
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
