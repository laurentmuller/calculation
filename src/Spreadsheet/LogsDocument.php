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
use App\Pdf\Html\HtmlBootstrapColor;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
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
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct(AbstractController $controller, private readonly LogFile $logFile)
    {
        parent::__construct($controller);
        $description = $this->trans('log.list.file', [
            '%file%' => $this->logFile->getFile(),
        ]);
        $this->setDescription($description);
    }

    public function render(): bool
    {
        $logFile = $this->logFile;
        $this->start('log.title', true);

        $sheet = $this->getActiveSheet();
        $row = $sheet->setHeaders([
            'log.fields.level' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'log.fields.channel' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'log.fields.createdAt' => HeaderFormat::date(Alignment::VERTICAL_TOP),
            'log.fields.message' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
            'log.fields.user' => HeaderFormat::instance(Alignment::VERTICAL_TOP),
        ]);

        $sheet->setFormat(3, 'dd/mm/yyyy hh:mm:ss')
            ->setColumnWidth(4, 140, true);

        $logs = $logFile->getLogs();
        foreach ($logs as $log) {
            $sheet->setRowValues($row, [
                $log->getLevel(true),
                $log->getChannel(true),
                $log->getCreatedAt(),
                $log->getMessage(),
                $log->getUser(),
            ]);
            $this->setBorderStyle($sheet, $row, $log->getLevel());
            ++$row;
        }
        $sheet->finish();

        return true;
    }

    private function getLevelColor(string $level): string
    {
        if (!isset($this->colors[$level])) {
            return $this->colors[$level] = match ($level) {
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::EMERGENCY,
                LogLevel::ERROR => HtmlBootstrapColor::DANGER->getPhpOfficeColor(),
                LogLevel::WARNING => HtmlBootstrapColor::WARNING->getPhpOfficeColor(),
                LogLevel::DEBUG => HtmlBootstrapColor::SECONDARY->getPhpOfficeColor(),
                // info, notice
                default => HtmlBootstrapColor::INFO->getPhpOfficeColor(),
            };
        }

        return $this->colors[$level];
    }

    private function setBorderStyle(WorksheetDocument $sheet, int $row, ?string $level): void
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
