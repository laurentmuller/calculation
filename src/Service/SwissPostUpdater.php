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

namespace App\Service;

use App\Database\SwissDatabase;
use App\Form\FormHelper;
use App\Interfaces\PropertyServiceInterface;
use App\Model\SwissPostUpdateResult;
use App\Reader\CSVReader;
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to import zip codes, cities and streets from Switzerland.
 *
 * @phpstan-type SwissAddressType =  array{
 *      0: int,
 *      1: int,
 *      2: int,
 *      4: int,
 *      6: string,
 *      8: string,
 *      9: string
 * }
 */
class SwissPostUpdater implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use ServiceMethodsSubscriberTrait;
    use TranslatorAwareTrait;

    /**
     * The pattern to parse validity date.
     */
    private const DATE_PATTERN = 'Ymd';

    /**
     * The import file extension.
     */
    private const FILE_EXTENSION = 'zip';

    /**
     * The record identifier containing the validity date.
     */
    private const REC_00_VALIDITY = 0;

    /**
     * The record identifier containing city data.
     */
    private const REC_01_CITY = 1;

    /**
     * The record identifier containing street data.
     */
    private const REC_04_STREET = 4;

    /**
     * The record identifier to stop processing data.
     */
    private const REC_05_STOP_PROCESS = 5;

    /**
     * The states file.
     */
    private const STATE_FILE = 'swiss_state.csv';

    public function __construct(
        private readonly ApplicationService $application,
        private readonly FormFactoryInterface $factory,
        private readonly SwissPostService $service
    ) {
    }

    /**
     * Creates a form to select the file to upload.
     *
     * @phpstan-return FormInterface<mixed>
     */
    public function createForm(array $data = []): FormInterface
    {
        $builder = $this->factory->createBuilder(data: $data);
        $helper = new FormHelper($builder, 'swisspost.fields.');
        $helper->field('file')
            ->help('swisspost.helps.file')
            ->addFileType(self::FILE_EXTENSION);
        $helper->field('overwrite')
            ->help('swisspost.helps.overwrite')
            ->addCheckboxType();

        return $helper->createForm();
    }

    /**
     * Import data from the given source file.
     */
    public function import(UploadedFile|string|null $sourceFile, bool $overwrite): SwissPostUpdateResult
    {
        $result = new SwissPostUpdateResult();
        $result->setOverwrite($overwrite);
        $result->setOldEntries($this->getTablesCount());
        if (!$this->validateInput($result, $sourceFile)) {
            return $result;
        }

        $tempFile = $this->tempFile($result);
        if (false === $tempFile) {
            return $result;
        }

        $reader = null;
        $archive = null;
        $database = null;

        try {
            $archive = $this->openArchive($result);
            if (!$archive instanceof \ZipArchive) {
                return $result;
            }
            if (!$this->validateArchive($result, $archive)) {
                return $result;
            }
            $reader = $this->openReader($result, $archive);
            if (!$reader instanceof CSVReader) {
                return $result;
            }
            $database = $this->openDatabase($tempFile);
            if (!$this->processStates($result, $database)) {
                return $result;
            }
            if (!$this->processReader($result, $database, $reader)) {
                return $result;
            }
        } finally {
            $this->closeReader($reader);
            $this->closeArchive($archive);
            $this->closeDatabase($result, $database);
            $this->renameDatabase($result, $tempFile);
        }

        return $this->updateValidity($result);
    }

    private function clean(string $str): string
    {
        /** @phpstan-var string */
        return \mb_convert_encoding(\trim($str), 'UTF-8', 'ISO-8859-1');
    }

    private function closeArchive(?\ZipArchive $archive): void
    {
        $archive?->close();
    }

    private function closeDatabase(SwissPostUpdateResult $result, ?SwissDatabase $database): void
    {
        if ($result->isValid()) {
            $database?->compact();
        }
        $database?->close();
    }

    private function closeReader(?CSVReader $reader): void
    {
        $reader?->close();
    }

    private function getDatabaseName(): string
    {
        return $this->service->getDatabaseName();
    }

    private function getLastImport(SwissPostUpdateResult $result): ?DatePoint
    {
        if ($result->isOverwrite() || !FileUtils::exists($this->getDatabaseName())) {
            return null;
        }

        return $this->application->getLastImport();
    }

    /**
     * Gets the record's count for all tables.
     *
     * @return array{city: int, state: int, street: int}
     */
    private function getTablesCount(): array
    {
        return $this->service->getTablesCount();
    }

    private function openArchive(SwissPostUpdateResult $result): ?\ZipArchive
    {
        $archive = new \ZipArchive();
        $error = $archive->open($result->getSourceFile());
        if (true !== $error) {
            $this->setError($result, 'archive_open', [
                '%name%' => $result->getSourceName(),
                '%error' => $error,
            ]);

            return null;
        }

        return $archive;
    }

    private function openDatabase(string $filename): SwissDatabase
    {
        $database = new SwissDatabase($filename);
        $database->beginTransaction();

        return $database;
    }

    private function openReader(SwissPostUpdateResult $result, \ZipArchive $archive): ?CSVReader
    {
        $name = $archive->getNameIndex(0);
        if (false === $name) {
            $this->setError($result, 'reader_name', [
                '%name%' => $result->getSourceName(),
            ]);

            return null;
        }

        $stream = $archive->getStream($name);
        if (!\is_resource($stream)) {
            $this->setError($result, 'reader_open', [
                '%name%' => $result->getSourceName(),
                '%stream%' => $name,
            ]);

            return null;
        }

        return new CSVReader(file: $stream, separator: ';');
    }

    /**
     * @phpstan-param SwissAddressType $data
     */
    private function processCity(SwissDatabase $database, array $data): bool
    {
        return $this->validateLength($data, 9)
            && $database->insertCity([
                $data[1],               // id
                $data[4],               // zip code
                $this->clean($data[8]), // city name
                $data[9],               // state (canton)
            ]);
    }

    private function processReader(SwissPostUpdateResult $result, SwissDatabase $database, CSVReader $reader): bool
    {
        $stop_process = false;
        /** @phpstan-var SwissAddressType $data */
        foreach ($reader as $data) { // @phpstan-ignore varTag.nativeType
            if ($stop_process) {
                break;
            }
            switch ($data[0]) {
                case self::REC_00_VALIDITY:
                    if (!$this->processValidity($result, $data)) {
                        return false;
                    }
                    break;
                case self::REC_01_CITY:
                    $result->addCity($this->processCity($database, $data));
                    break;
                case self::REC_04_STREET:
                    $result->addStreet($this->processStreet($database, $data));
                    break;
                case self::REC_05_STOP_PROCESS:
                    $stop_process = true;
                    break;
            }
            $this->toggleTransaction($result, $database);
        }
        $database->commitTransaction();

        // city or street imported?
        $entries = $result->getValidEntries();
        if (0 === $entries['city'] || 0 === $entries['street']) {
            return $this->setError($result, 'archive_empty', ['%name%' => $result->getSourceName()]);
        }

        return true;
    }

    private function processStates(SwissPostUpdateResult $result, SwissDatabase $database): bool
    {
        $filename = FileUtils::buildPath(\dirname($this->getDatabaseName()), self::STATE_FILE);
        if (!FileUtils::exists($filename) || FileUtils::empty($filename)) {
            return $this->setError($result, 'file_states');
        }

        $reader = new CSVReader(file: $filename, separator: ';');
        /** @phpstan-var array{0: string, 1: string} $data */
        foreach ($reader as $data) {
            $result->addState($database->insertState($data));
        }
        $reader->close();

        return true;
    }

    /**
     * @phpstan-param SwissAddressType $data
     */
    private function processStreet(SwissDatabase $database, array $data): bool
    {
        return $this->validateLength($data, 6)
            && $database->insertStreet([
                $data[2],                         // city identifier
                \ucfirst($this->clean($data[6])), // street name
            ]);
    }

    /**
     * @phpstan-param SwissAddressType $data
     */
    private function processValidity(SwissPostUpdateResult $result, array $data): bool
    {
        $validity = null;
        if ($this->validateLength($data, 1)) {
            $validity = DatePoint::createFromFormat(self::DATE_PATTERN, (string) $data[1]);
            $validity = DateUtils::removeTime($validity);
        }
        if (!$validity instanceof DatePoint) {
            return $this->setError($result, 'validity_none', ['%name%' => $result->getSourceName()]);
        }
        $result->setValidity($validity);
        $lastImport = $this->getLastImport($result);
        if ($lastImport instanceof DatePoint && $validity <= $lastImport) {
            return $this->setError($result, 'validity_before', [
                '%validity%' => FormatUtils::formatDate($validity, \IntlDateFormatter::LONG),
                '%import%' => FormatUtils::formatDate($lastImport, \IntlDateFormatter::LONG),
                '%name%' => $result->getSourceName(),
            ]);
        }

        return true;
    }

    private function renameDatabase(SwissPostUpdateResult $result, string $source): void
    {
        if ($result->isValid() && !FileUtils::rename($source, $this->getDatabaseName(), true)) {
            $this->setError($result, 'database_rename');
        }
    }

    private function setError(SwissPostUpdateResult $result, string $id, array $parameters = []): false
    {
        $result->setError($this->trans("swisspost.error.$id", $parameters));

        return false;
    }

    private function tempFile(SwissPostUpdateResult $result): string|false
    {
        $file = FileUtils::tempFile();
        if (null === $file) {
            return $this->setError($result, 'file_temp');
        }

        return $file;
    }

    private function toggleTransaction(SwissPostUpdateResult $result, SwissDatabase $database): void
    {
        if (0 === $result->getValidEntriesCount() % 50_000) {
            $database->commitTransaction();
            $database->beginTransaction();
        }
    }

    private function updateValidity(SwissPostUpdateResult $result): SwissPostUpdateResult
    {
        if ($result->isValid() && $result->getValidity() instanceof DatePoint) {
            $this->application->setProperty(PropertyServiceInterface::P_DATE_IMPORT, $result->getValidity());
        }

        return $result;
    }

    private function validateArchive(SwissPostUpdateResult $result, \ZipArchive $archive): bool
    {
        $count = $archive->count();

        return match ($count) {
            0 => $this->setError($result, 'archive_empty', [
                '%name%' => $result->getSourceName(),
            ]),
            1 => true,
            default => $this->setError($result, 'archive_not_one', [
                '%name%' => $result->getSourceName(),
                '%count%' => $count,
            ]),
        };
    }

    private function validateInput(SwissPostUpdateResult $result, UploadedFile|string|null $sourceFile): bool
    {
        if (null === $sourceFile || '' === $sourceFile) {
            return $this->setError($result, 'file_none');
        }
        if ($sourceFile instanceof UploadedFile) {
            if (!$sourceFile->isValid()) {
                return $this->setError($result, 'file_empty');
            }

            $result->setSourceName($sourceFile->getClientOriginalName());
            $sourceFile = $sourceFile->getPathname();
        } else {
            $result->setSourceName(\basename($sourceFile));
        }
        $result->setSourceFile($sourceFile);

        if (!FileUtils::exists($sourceFile)) {
            return $this->setError($result, 'file_not_exist');
        }
        if (FileUtils::empty($sourceFile)) {
            return $this->setError($result, 'file_empty');
        }
        if (StringUtils::equalIgnoreCase($sourceFile, $this->getDatabaseName())) {
            return $this->setError($result, 'database_open');
        }

        return true;
    }

    private function validateLength(array $data, int $index): bool
    {
        return \count($data) > $index;
    }
}
