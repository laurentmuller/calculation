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
use App\Entity\Log;
use App\Service\LogService;
use App\Util\Utils;
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Spreadsheet document for application logs.
 */
class LogsDocument extends AbstractDocument
{
    private ?SqlFormatter $formatter = null;

    /**
     * Constructor.
     *
     * @param array{
     *      file: string,
     *      logs: array<int, Log>,
     *      levels: array<string, int>,
     *      channels: array<string, int>} $entries
     */
    public function __construct(AbstractController $controller, private readonly array $entries)
    {
        parent::__construct($controller);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // initialize
        $this->start('log.title');

        // headers
        $this->setHeaderValues([
            'log.fields.createdAt' => [Alignment::HORIZONTAL_CENTER, Alignment::VERTICAL_TOP],
            'log.fields.channel' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'log.fields.level' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'log.fields.message' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'log.fields.user' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
        ]);

        // formats
        $this->setFormat(1, 'dd/mm/yyyy hh:mm:ss')
            ->setColumnWidth(4, 120, true);

        /** @var Log[] $logs */
        $logs = $this->entries['logs'];
        LogService::sortLogs($logs);

        $row = 2;
        foreach ($logs as $log) {
            $this->setRowValues($row, [
                $log->getCreatedAt(),
                $this->capitalize($log->getChannel()),
                $this->capitalize($log->getLevel()),
                $this->formatMessage($log),
                $log->getUser(),
            ]);
            $this->setBorderStyle($row, $log->getLevel());
            ++$row;
        }

        // style for message
        --$row;
        $this->getActiveSheet()
            ->getStyle("D2:D$row")
            ->getFont()
            ->setName('Courier New')
            ->setSize(9);

        $this->finish();

        return true;
    }

    private function capitalize(?string $channel): ?string
    {
        return null !== $channel ? Utils::capitalize($channel) : null;
    }

    /**
     * Gets the message for the given log.
     */
    private function formatMessage(Log $log): string
    {
        $message = (string) $log->getMessage();
        if ('doctrine' === $log->getChannel()) {
            $message = $this->formatSql($message);
        }
        if (!empty($log->getContext())) {
            $message .= "\nContext:\n" . (string) Utils::exportVar($log->getContext());
        }
        if (!empty($log->getExtra())) {
            $message .= "\nExtra:\n" . (string) Utils::exportVar($log->getExtra());
        }

        return $message;
    }

    /**
     * Format the given Sql query.
     */
    private function formatSql(string $sql): string
    {
        if (null === $this->formatter) {
            $this->formatter = new SqlFormatter(new NullHighlighter());
        }

        return $this->formatter->format($sql);
    }

    /**
     * Sets the border style depending on the log level.
     */
    private function setBorderStyle(int $row, ?string $level): void
    {
        $rgb = match ($level) {
            'warning' => 'ffc107',
            'error', 'critical', 'alert', 'emergency' => 'dc3545',
            'debug' => '007bff',
            'info', 'notice' => '17a2b8',
            default => null,
        };

        if (null !== $rgb) {
            $this->getActiveSheet()
                ->getStyle("A$row")
                ->getBorders()
                ->getLeft()
                ->setBorderStyle(Border::BORDER_THICK)
                ->getColor()->setARGB($rgb);
        }
    }
}
