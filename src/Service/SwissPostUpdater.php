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
use App\Traits\LoggerAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\CSVReader;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to import zip codes, cities and streets from Switzerland.
 *
 * @psalm-type SwissAddressType =  array{
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
    use ServiceSubscriberTrait;
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

    private readonly string $databaseName;

    public function __construct(private readonly ApplicationService $application, private readonly FormFactoryInterface $factory, SwissPostService $service)
    {
        $this->databaseName = $service->getDatabaseName();
    }

    /**
     * Creates a form to select the file to upload.
     *
     * @psalm-return FormInterface<mixed>
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
            ->notRequired()
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
        if (!$this->validateInput($result, $sourceFile)) {
            return $result;
        }
        $temp_file = FileUtils::tempFile();
        if (null === $temp_file) {
            return $this->setError($result, 'file_temp');
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
            $database = $this->openDatabase($temp_file);
            if (!$this->processStates($result, $database)) {
                return $result;
            }
            if (!$this->processReader($result, $database, $reader)) {
                return $result;
            }
            if (0 === $result->getValidEntriesCount()) {
                return $this->setError($result, 'archive_empty', ['%name%' => $result->getSourceName()]);
            }
        } finally {
            $this->closeReader($result, $reader);
            $this->closeArchive($result, $archive);
            $this->compactDatabase($result, $database);
            $this->closeDatabase($result, $database);
            $this->renameDatabase($result, $temp_file);
        }
        $this->updateValidity($result);

        return $result;
    }

    private function clean(string $str): string
    {
        return \mb_convert_encoding(\trim($str), 'UTF-8', 'ISO-8859-1');
    }

    private function closeArchive(SwissPostUpdateResult $result, ?\ZipArchive $archive): void
    {
        try {
            $archive?->close();
        } catch (\Exception $e) {
            $this->logResult($result, 'archive_close', $e);
        }
    }

    private function closeDatabase(SwissPostUpdateResult $result, ?SwissDatabase $database): void
    {
        try {
            $database?->close();
        } catch (\Exception $e) {
            $this->logResult($result, 'database_close', $e);
        }
    }

    private function closeReader(SwissPostUpdateResult $result, ?CSVReader $reader): void
    {
        try {
            $reader?->close();
        } catch (\Exception $e) {
            $this->logResult($result, 'reader_close', $e);
        }
    }

    private function compactDatabase(SwissPostUpdateResult $result, ?SwissDatabase $database): void
    {
        try {
            if ($result->isValid()) {
                $database?->compact();
            }
        } catch (\Exception $e) {
            $this->logResult($result, 'database_compact', $e);
        }
    }

    private function getLastImport(SwissPostUpdateResult $result): ?\DateTimeInterface
    {
        if ($result->isOverwrite() || !FileUtils::exists($this->databaseName)) {
            return null;
        }

        return $this->application->getLastImport();
    }

    private function logResult(SwissPostUpdateResult $result, string $id, \Exception $e): void
    {
        $this->setError($result, $id);
        $this->logException($e, $result->getError());
    }

    private function openArchive(SwissPostUpdateResult $result): ?\ZipArchive
    {
        $archive = new \ZipArchive();
        $error = $archive->open($result->getSourceFile());
        if (\is_int($error)) {
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
        if (false === $stream) {
            $this->setError($result, 'reader_open', [
                '%name%' => $result->getSourceName(),
                '%stream%' => $name,
            ]);

            return null;
        }

        return new CSVReader(file: $stream, separator: ';');
    }

    /**
     * @psalm-param SwissAddressType $data
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
        /** @psalm-var SwissAddressType|null $data */
        foreach ($reader as $data) { // @phpstan-ignore-line
            if ($stop_process || null === $data) {
                break;
            }
            switch ($data[0]) {
                case self::REC_00_VALIDITY:
                    if (!$this->processValidity($result, $data)) { // @phpstan-ignore-line
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

        return true;
    }

    private function processStates(SwissPostUpdateResult $result, SwissDatabase $database): bool
    {
        $filename = FileUtils::buildPath(\dirname($this->databaseName), self::STATE_FILE);
        if (!FileUtils::exists($filename) || FileUtils::empty($filename)) {
            $this->setError($result, 'file_states');

            return false;
        }

        $reader = new CSVReader(file: $filename, separator: ';');
        /** @psalm-var array{0: string, 1: string} $data */
        foreach ($reader as $data) {
            $result->addState($database->insertState($data));
        }
        $reader->close();

        return true;
    }

    /**
     * @psalm-param SwissAddressType $data
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
     * @psalm-param SwissAddressType $data
     */
    private function processValidity(SwissPostUpdateResult $result, array $data): bool
    {
        $validity = null;
        if ($this->validateLength($data, 1)) {
            $validity = \DateTime::createFromFormat(self::DATE_PATTERN, (string) $data[1]);
            if ($validity instanceof \DateTime) {
                $validity = $validity->setTime(0, 0);
            }
        }
        if (!$validity instanceof \DateTimeInterface) {
            $this->setError($result, 'validity_none', ['%name%' => $result->getSourceName()]);

            return false;
        }
        $result->setValidity($validity);
        $lastImport = $this->getLastImport($result);
        if ($lastImport instanceof \DateTimeInterface && $validity <= $lastImport) {
            $this->setError($result, 'validity_before', [
                '%validity%' => FormatUtils::formatDate($validity, \IntlDateFormatter::LONG),
                '%import%' => FormatUtils::formatDate($lastImport, \IntlDateFormatter::LONG),
                '%name%' => $result->getSourceName(),
            ]);

            return false;
        }

        return true;
    }

    private function renameDatabase(SwissPostUpdateResult $result, string $source): void
    {
        if ($result->isValid() && !FileUtils::rename($source, $this->databaseName, true)) {
            $this->setError($result, 'database_rename');
        }
    }

    private function setError(SwissPostUpdateResult $result, string $id, array $parameters = []): SwissPostUpdateResult
    {
        return $result->setError($this->trans("swisspost.error.$id", $parameters));
    }

    private function toggleTransaction(SwissPostUpdateResult $result, SwissDatabase $database): void
    {
        if (0 === $result->getValidEntriesCount() % 50_000) {
            $database->commitTransaction();
            $database->beginTransaction();
        }
    }

    private function updateValidity(SwissPostUpdateResult $result): void
    {
        if (!$result->isValid()) {
            return;
        }
        $validity = $result->getValidity();
        if (!$validity instanceof \DateTimeInterface) {
            return;
        }
        $this->application->setProperty(PropertyServiceInterface::P_DATE_IMPORT, $validity);
    }

    private function validateArchive(SwissPostUpdateResult $result, \ZipArchive $archive): bool
    {
        $count = $archive->count();
        switch ($count) {
            case 0:
                $this->setError($result, 'archive_empty', ['%name%' => $result->getSourceName()]);

                return false;

            case 1:
                return true;

            default:
                $this->setError($result, 'archive_not_one', ['%name%' => $result->getSourceName(), '%count%' => $count]);

                return false;
        }
    }

    private function validateInput(SwissPostUpdateResult $result, UploadedFile|string|null $sourceFile): bool
    {
        if (null === $sourceFile || '' === $sourceFile) {
            $this->setError($result, 'file_none');

            return false;
        }
        if ($sourceFile instanceof UploadedFile) {
            $result->setSourceName($sourceFile->getClientOriginalName());
            $sourceFile = $sourceFile->getPathname();
        } else {
            $result->setSourceName(\basename($sourceFile));
        }
        $result->setSourceFile($sourceFile);

        if (!FileUtils::exists($sourceFile)) {
            $this->setError($result, 'file_not_exist');

            return false;
        }
        if (FileUtils::empty($sourceFile)) {
            $this->setError($result, 'file_empty');

            return false;
        }
        if (StringUtils::equalIgnoreCase($sourceFile, $this->databaseName)) {
            $this->setError($result, 'database_open');

            return false;
        }

        return true;
    }

    private function validateLength(array $data, int $length): bool
    {
        return \count($data) > $length;
    }
}
