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
use Doctrine\SqlFormatter\NullHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

/**
 * Spreadsheet document for application logs.
 */
class LogsDocument extends AbstractDocument
{
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

        $row = 2;
        $logs = $logFile->getLogs();
        $formatter = new SqlFormatter(new NullHighlighter());
        foreach ($logs as $log) {
            $this->setRowValues($row, [
                $log->getCreatedAt(),
                $log->getChannel(true),
                $log->getLevel(true),
                $log->formatMessage($formatter),
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
