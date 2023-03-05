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
 *      4: string|int,
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
     * The record identifier containing city data.
     */
    private const REC_CITY = 1;

    /**
     * The record identifier to stop the process.
     */
    private const REC_STOP_PROCESS = 5;

    /**
     * The record identifier containing street data.
     */
    private const REC_STREET = 4;

    /**
     * The record identifier containing the validity date.
     */
    private const REC_VALIDITY = 0;

    /**
     * The states file.
     */
    private const STATE_FILE = 'swiss_state.csv';

    private ?\ZipArchive $archive = null;
    private ?SwissDatabase $database = null;
    private readonly string $databaseName;
    private SwissPostUpdateResult $results;
    private ?string $sourceName = null;

    /**
     * @var resource|false
     *
     * @psalm-var resource|closed-resource|false
     */
    private $stream = false;

    public function __construct(private readonly ApplicationService $application, private readonly FormFactoryInterface $factory, SwissPostService $service)
    {
        $this->databaseName = $service->getDatabaseName();
        $this->results = new SwissPostUpdateResult();
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
        $this->results = new SwissPostUpdateResult();
        $this->results->setOverwrite($overwrite);

        // check source file
        if (null === $sourceFile || '' === $sourceFile) {
            return $this->setError('file_empty');
        }

        // get path and name
        if ($sourceFile instanceof UploadedFile) {
            $this->sourceName = $sourceFile->getClientOriginalName();
            $sourceFile = $sourceFile->getPathname();
        } else {
            $this->sourceName = \basename($sourceFile);
        }

        // exist and not empty?
        if (!FileUtils::exists($sourceFile) || FileUtils::empty($sourceFile)) {
            return $this->setError('file_not_exist');
        }

        // same as current database?
        if (StringUtils::equalIgnoreCase($sourceFile, $this->databaseName)) {
            return $this->setError('database_open');
        }

        // create a temporary file
        if (null === $temp_name = FileUtils::tempfile('sql')) {
            return $this->setError('file_temp');
        }

        try {
            // open archive
            if (!$this->openArchive($sourceFile)) {
                return $this->results;
            }
            if (!$this->validateArchive()) {
                return $this->results;
            }

            // open stream
            if (!$this->openStream()) {
                return $this->results;
            }

            // open database
            $this->openDatabase($temp_name);

            // process states
            $this->processStates();

            // process stream
            if (!$this->processStream()) {
                return $this->results;
            }

            // imported data?
            if (0 === $this->results->getValids()) {
                return $this->setError('archive_empty', ['%name%' => $this->sourceName]);
            }
        } finally {
            // close all
            $this->closeStream();
            $this->closeArchive();
            $this->compactDatabase();
            $this->closeDatabase();
            $this->renameDatabase($temp_name);
        }

        // save date
        $this->updateValidity();

        return $this->results;
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
    private function closeArchive(): void
    {
        try {
            $this->archive?->close();
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('swisspost.error.archive_close'));
        } finally {
            $this->archive = null;
        }
    }

    /**
     * Close the database.
     */
    private function closeDatabase(): void
    {
        try {
            $this->database?->close();
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('swisspost.error.database_close'));
        } finally {
            $this->database = null;
        }
    }

    /**
     * Close the Zip archive entry stream.
     */
    private function closeStream(): void
    {
        try {
            if (\is_resource($this->stream)) {
                \fclose($this->stream);
            }
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('swisspost.error.stream_close'));
        } finally {
            $this->stream = false;
        }
    }

    private function compactDatabase(): void
    {
        try {
            if ($this->results->isValid()) {
                $this->database?->compact();
            }
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('swisspost.error.database_compact'));
        }
    }

    /**
     * Gets the date of the last import.
     */
    private function getLastImport(): ?\DateTimeInterface
    {
        if ($this->results->isOverwrite() || !FileUtils::exists($this->databaseName)) {
            return null;
        }

        return $this->application->getLastImport();
    }

    /**
     * Open the Zip archive.
     */
    private function openArchive(string $sourceFile): bool
    {
        // open archive
        $this->archive = new \ZipArchive();
        $error = $this->archive->open($sourceFile);
        if (true !== $error) {
            $this->setError('archive_open', [
                '%name%' => $this->sourceName,
                '%error' => $error,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Open the database.
     */
    private function openDatabase(string $filename): void
    {
        $this->database = new SwissDatabase($filename);
        $this->database->beginTransaction();
    }

    /**
     * Open the Zip archive entry stream.
     */
    private function openStream(): bool
    {
        // open entry
        if (null !== $this->archive) {
            $streamName = (string) $this->archive->getNameIndex(0);
            if (false === $this->stream = $this->archive->getStream($streamName)) {
                $this->setError('stream_open', [
                    '%name%' => $this->sourceName,
                    '%streamName%' => $streamName,
                ]);
                $this->closeArchive();

                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Insert a city record to the database.
     *
     * @psalm-param SwissAddressType $data
     */
    private function processCity(array $data): void
    {
        if ($this->validateLength($data, 9) && null !== $this->database && $this->database->insertCity([
                $data[1],         // id
                (int) $data[4],         // zip code
                $this->clean($data[8]), // city name
                $data[9],               // state (canton)
        ])) {
            $this->results->addValidCities();
        } else {
            $this->results->addErrorCities();
        }
    }

    /**
     * Imports the states (canton) to the database.
     */
    private function processStates(): void
    {
        $filename = FileUtils::buildPath(\dirname($this->databaseName), self::STATE_FILE);
        if (FileUtils::exists($filename) && false !== ($handle = \fopen($filename, 'r'))) {
            while (false !== ($data = \fgetcsv($handle, 0, ';'))) {
                /** @psalm-var array{0: string, 1: string} $data */
                if (null !== $this->database && $this->database->insertState($data)) {
                    $this->results->addValidStates();
                } else {
                    $this->results->addErrorStates();
                }
            }
            \fclose($handle);
        }
    }

    /**
     * Process the Zip archive entry stream.
     */
    private function processStream(): bool
    {
        /** @var resource $stream */
        $stream = $this->stream;
        $process = true;

        while ($process) {
            /** @psalm-var SwissAddressType|bool|null $data */
            $data = \fgetcsv(stream: $stream, separator: ';');
            if (!\is_array($data)) {
                break;
            }

            switch ($data[0]) {
                case self::REC_VALIDITY:
                    if (!$this->processValidity($data)) {
                        $this->closeStream();

                        return false;
                    }
                    break;
                case self::REC_CITY:
                    $this->processCity($data);
                    break;
                case self::REC_STREET:
                    $this->processStreet($data);
                    break;
                case self::REC_STOP_PROCESS:
                    $process = false;
                    break;
            }

            // commit
            $this->toggleTransaction();
        }

        // last commit
        $this->database?->commitTransaction();

        // close
        $this->closeStream();

        return true;
    }

    /**
     * Insert a street record to the database.
     *
     * @psalm-param SwissAddressType $data
     */
    private function processStreet(array $data): void
    {
        if ($this->validateLength($data, 6) && null !== $this->database && $this->database->insertStreet([
                $data[2],                         // city identifier
                \ucfirst($this->clean($data[6])), // street name
        ])) {
            $this->results->addValidStreets();
        } else {
            $this->results->addErrorStreets();
        }
    }

    /**
     * Process the validity record.
     *
     * @psalm-param SwissAddressType $data
     */
    private function processValidity(array $data): bool
    {
        $validity = null;
        if ($this->validateLength($data, 1)) {
            $validity = \DateTime::createFromFormat(self::DATE_PATTERN, (string) $data[1]);
            if ($validity instanceof \DateTime) {
                $validity = $validity->setTime(0, 0);
            }
        }

        if (!$validity instanceof \DateTimeInterface) {
            $this->setError('validity_none', ['%name%' => $this->sourceName]);

            return false;
        }

        $this->results->setValidity($validity);
        $lastImport = $this->getLastImport();
        if ($lastImport instanceof \DateTimeInterface && $validity <= $lastImport) {
            $this->setError('validity_before', [
                '%validity%' => FormatUtils::formatDate($validity, \IntlDateFormatter::LONG),
                '%import%' => FormatUtils::formatDate($lastImport, \IntlDateFormatter::LONG),
                '%name%' => $this->sourceName,
            ]);

            return false;
        }

        return true;
    }

    /**
     * Rename the database.
     */
    private function renameDatabase(string $source): void
    {
        if ($this->results->isValid() && !FileUtils::rename($source, $this->databaseName, true)) {
            $this->setError('database_rename');
        }
    }

    /**
     * Sets error message.
     */
    private function setError(string $id, array $parameters = []): SwissPostUpdateResult
    {
        return $this->results->setError($this->trans("swisspost.error.$id", $parameters));
    }

    /**
     * Commit and begin a transaction, if applicable.
     */
    private function toggleTransaction(): void
    {
        if (0 === $this->results->getValids() % 50_000) {
            $this->database?->commitTransaction();
            $this->database?->beginTransaction();
        }
    }

    /**
     * Update the last imported date.
     */
    private function updateValidity(): void
    {
        if ($this->results->isValid() && null !== $validity = $this->results->getValidity()) {
            $this->application->setProperty(PropertyServiceInterface::P_DATE_IMPORT, $validity);
        }
    }

    private function validateArchive(): bool
    {
        // check count
        switch ($this->archive?->count()) {
            case 0:
                $this->setError('archive_empty', ['%name%' => $this->sourceName]);
                $this->closeArchive();

                return false;

            case 1:
                return true;

            default:
                $this->setError('archive_not_one', ['%name%' => $this->sourceName]);
                $this->closeArchive();

                return false;
        }
    }

    private function validateLength(array $data, int $length): bool
    {
        return \count($data) > $length;
    }
}
