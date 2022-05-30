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

use App\Entity\Log;
use App\Service\LogService;
use App\Util\Utils;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Spreadsheet document for application logs.
 *
 * @extends AbstractArrayDocument<mixed>
 */
class LogsDocument extends AbstractArrayDocument
{
    /**
     * {@inheritdoc}
     */
    public function setCellValue(Worksheet $sheet, int $columnIndex, int $rowIndex, $value): self
    {
        parent::setCellValue($sheet, $columnIndex, $rowIndex, $value);

        if (4 === $columnIndex && $rowIndex > 1) {
            $sheet->getCellByColumnAndRow($columnIndex, $rowIndex)->getStyle()
                ->getFont()->setName('Courier New')->setSize(9);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('log.title');

        // headers
        $this->setHeaderValues([
            'log.fields.createdAt' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'log.fields.channel' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'log.fields.level' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'log.fields.message' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
        ]);

        // formats
        $this->setFormat(1, 'dd/mm/yyyy hh:mm:ss')
            ->setColumnWidth(4, 120, true);

        // rows
        $row = 2;
        /** @var Log[] $logs */
        $logs = $entities['logs'];
        foreach ($logs as $log) {
            $this->setRowValues($row++, [
                $log->getCreatedAt(),
                LogService::getChannel((string) $log->getChannel(), true),
                LogService::getLevel((string) $log->getLevel(), true),
                $this->getMessage($log),
            ]);
        }

        $this->finish();

        return true;
    }

    /**
     * Format the given Sql query.
     */
    private function formatSql(string $sql): string
    {
        /** @var SqlFormatter|null $formatter */
        static $formatter;
        if (null === $formatter) {
            $formatter = new SqlFormatter(new NullHighlighter());
        }

        return $formatter->format($sql);
    }

    /**
     * Gets the message for the given log.
     */
    private function getMessage(Log $log): string
    {
        $message = 'doctrine' === $log->getChannel() ? $this->formatSql((string) $log->getMessage()) : (string) $log->getMessage();
        if (!empty($log->getContext())) {
            $message .= "\n" . (string) Utils::exportVar((array) $log->getContext());
        }
        if (!empty($log->getExtra())) {
            $message .= "\n" . (string) Utils::exportVar($log->getExtra());
        }

        return $message;
    }
}
