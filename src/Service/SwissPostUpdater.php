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
use App\Util\CSVReader;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use App\Util\StringUtils;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
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
     */
    public function createForm(array $data = []): FormInterface
    {
        $builder = $this->factory->createBuilder(data: $data);
        $helper = new FormHelper($builder, 'swisspost.fields.');
        $types = ['application/zip', 'application/x-zip-compressed'];

        // file constraints
        $constraint = new File([
            'mimeTypes' => $types,
            'mimeTypesMessage' => $this->trans('swisspost.error.mime_type'),
        ]);

        // fields
        $helper->field('file')
            ->help('swisspost.helps.file')
            ->constraints($constraint)
            ->updateAttribute('accept', \implode(',', $types))
            ->addFileType();

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

        // check input
        if (null !== $this->validateInput($result, $sourceFile)) {
            return $result;
        }

        // create a temporary file
        if (null === $temp_name = FileUtils::tempfile('sql')) {
            return $this->setError($result, 'file_temp');
        }

        $reader = null;
        $archive = null;
        $database = null;

        try {
            if (null === $archive = $this->openArchive($result)) {
                return $result;
            }
            if (!$this->validateArchive($result, $archive)) {
                return $result;
            }
            if (null === $reader = $this->openReader($result, $archive)) {
                return $result;
            }

            $database = $this->openDatabase($temp_name);

            if (!$this->processStates($result, $database)) {
                return $result;
            }
            if (!$this->processReader($result, $database, $reader)) {
                return $result;
            }

            if (0 === $result->getValidCount()) {
                return $this->setError($result, 'archive_empty', ['%name%' => $result->getSourceName()]);
            }
        } finally {
            $this->closeReader($result, $reader);
            $this->closeArchive($result, $archive);
            $this->compactDatabase($result, $database);
            $this->closeDatabase($result, $database);
            $this->renameDatabase($result, $temp_name);
        }

        // save date
        $this->updateValidity($result);

        return $result;
    }

    /**
     * Strip whitespace and convert the given string from ISO-8859-1 to UTF-8.
     *
     * @param string $str the string to clean
     */
    private function clean(string $str): string
    {
        return \mb_convert_encoding(\trim($str), 'UTF-8', 'ISO-8859-1');
    }

    /**
     * Close the Zip archive.
     */
    private function closeArchive(SwissPostUpdateResult $result, ?\ZipArchive $archive): void
    {
        try {
            $archive?->close();
        } catch (\Exception $e) {
            $this->logEx($result, 'archive_close', $e);
        }
    }

    /**
     * Close the database.
     */
    private function closeDatabase(SwissPostUpdateResult $result, ?SwissDatabase $database): void
    {
        try {
            $database?->close();
        } catch (\Exception $e) {
            $this->logEx($result, 'database_close', $e);
        }
    }

    /**
     * Close the Zip archive entry reader.
     */
    private function closeReader(SwissPostUpdateResult $result, ?CSVReader $reader): void
    {
        try {
            $reader?->close();
        } catch (\Exception $e) {
            $this->logEx($result, 'reader_close', $e);
        }
    }

    private function compactDatabase(SwissPostUpdateResult $result, ?SwissDatabase $database): void
    {
        try {
            if ($result->isValid()) {
                $database?->compact();
            }
        } catch (\Exception $e) {
            $this->logEx($result, 'database_compact', $e);
        }
    }

    /**
     * Gets the date of the last import.
     */
    private function getLastImport(SwissPostUpdateResult $result): ?\DateTimeInterface
    {
        if ($result->isOverwrite() || !FileUtils::exists($this->databaseName)) {
            return null;
        }

        return $this->application->getLastImport();
    }

    private function logEx(SwissPostUpdateResult $result, string $id, \Exception $e): void
    {
        $this->setError($result, $id);
        $this->logException($e, $result->getError());
    }

    /**
     * Open the Zip archive.
     */
    private function openArchive(SwissPostUpdateResult $result): ?\ZipArchive
    {
        // open archive
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

    /**
     * Open the database.
     */
    private function openDatabase(string $filename): SwissDatabase
    {
        $database = new SwissDatabase($filename);
        $database->beginTransaction();

        return $database;
    }

    /**
     * Open the Zip archive entry reader.
     */
    private function openReader(SwissPostUpdateResult $result, \ZipArchive $archive): ?CSVReader
    {
        if (false === $name = $archive->getNameIndex(0)) {
            $this->setError($result, 'reader_name', [
                '%name%' => $result->getSourceName(),
            ]);

            return null;
        }
        if (false === $stream = $archive->getStream($name)) {
            $this->setError($result, 'reader_open', [
                '%name%' => $result->getSourceName(),
                '%stream%' => $name,
            ]);

            return null;
        }

        return new CSVReader(file: $stream, separator: ';');
    }

    /**
     * Insert a city record to the database.
     *
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

    /**
     * Process the Zip archive entry stream.
     */
    private function processReader(SwissPostUpdateResult $result, SwissDatabase $database, CSVReader $reader): bool
    {
        $stop_process = false;

        /** @psalm-var SwissAddressType|null $data */
        foreach ($reader as $data) {
            if ($stop_process || null === $data) {
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

        return true;
    }

    /**
     * Imports the states (canton) to the database.
     */
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
     * Insert a street record to the database.
     *
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
     * Process the validity record.
     *
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

    /**
     * Rename the database.
     */
    private function renameDatabase(SwissPostUpdateResult $result, string $source): void
    {
        if ($result->isValid() && !FileUtils::rename($source, $this->databaseName, true)) {
            $this->setError($result, 'database_rename');
        }
    }

    /**
     * Sets error message.
     */
    private function setError(SwissPostUpdateResult $result, string $id, array $parameters = []): SwissPostUpdateResult
    {
        return $result->setError($this->trans("swisspost.error.$id", $parameters));
    }

    /**
     * Commit and begin a transaction, if applicable.
     */
    private function toggleTransaction(SwissPostUpdateResult $result, SwissDatabase $database): void
    {
        if (0 === $result->getValidCount() % 50_000) {
            $database->commitTransaction();
            $database->beginTransaction();
        }
    }

    /**
     * Update the last imported date.
     */
    private function updateValidity(SwissPostUpdateResult $result): void
    {
        if ($result->isValid() && null !== $validity = $result->getValidity()) {
            $this->application->setProperty(PropertyServiceInterface::P_DATE_IMPORT, $validity);
        }
    }

    private function validateArchive(SwissPostUpdateResult $result, \ZipArchive $archive): bool
    {
        // check count
        switch ($archive->count()) {
            case 0:
                $this->setError($result, 'archive_empty', ['%name%' => $result->getSourceName()]);

                return false;

            case 1:
                return true;

            default:
                $this->setError($result, 'archive_not_one', ['%name%' => $result->getSourceName()]);

                return false;
        }
    }

    private function validateInput(SwissPostUpdateResult $result, UploadedFile|string|null $sourceFile): ?SwissPostUpdateResult
    {
        if (null === $sourceFile || '' === $sourceFile) {
            return $this->setError($result, 'file_empty');
        }

        if ($sourceFile instanceof UploadedFile) {
            $result->setSourceName($sourceFile->getClientOriginalName());
            $sourceFile = $sourceFile->getPathname();
        } else {
            $result->setSourceName(\basename($sourceFile));
        }
        $result->setSourceFile($sourceFile);

        if (!FileUtils::exists($sourceFile) || FileUtils::empty($sourceFile)) {
            return $this->setError($result, 'file_not_exist');
        }

        if (StringUtils::equalIgnoreCase($sourceFile, $this->databaseName)) {
            return $this->setError($result, 'database_open');
        }

        return null;
    }

    private function validateLength(array $data, int $length): bool
    {
        return \count($data) > $length;
    }
}
