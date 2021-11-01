<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Service;

use App\Database\SwissDatabase;
use App\Form\FormHelper;
use App\Interfaces\ApplicationServiceInterface;
use App\Model\SwissPostUpdateResult;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to import zip codes, cities and streets from Switzerland.
 *
 * @author Laurent Muller
 */
class SwissPostUpdater
{
    use TranslatorTrait;

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

    private ApplicationService $application;

    private ?\ZipArchive $archive = null;
    private ?SwissDatabase $database = null;
    private string $databaseName;
    private string $dataDirectory;
    private FormFactoryInterface $factory;
    private ?SwissPostUpdateResult $results = null;
    private ?string $sourceName = null;

    /** @var resource|bool */
    private $stream = false;

    public function __construct(TranslatorInterface $translator, ApplicationService $application, FormFactoryInterface $factory, SwissPostService $service)
    {
        $this->translator = $translator;
        $this->application = $application;
        $this->factory = $factory;
        $this->databaseName = $service->getDatabaseName();
        $this->dataDirectory = $service->getDataDirectory();
    }

    /**
     * Creates the edit form.
     */
    public function createForm(): FormInterface
    {
        $builder = $this->factory->createBuilder(FormType::class);
        $helper = new FormHelper($builder, 'swisspost.fields.');

        // file constraints
        $constraints = new File([
            'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
            'mimeTypesMessage' => $this->trans('swisspost.error.mime_type'),
        ]);

        // fields
        $helper->field('file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', 'application/x-zip-compressed')
            ->addFileType();

        return $helper->createForm();
    }

    /**
     * Import data from the given source file.
     *
     * @param UploadedFile|string|null $sourceFile the source file to import
     */
    public function import($sourceFile): SwissPostUpdateResult
    {
        $this->results = new SwissPostUpdateResult();

        // check source file
        if (null === $sourceFile || '' === $sourceFile) {
            return $this->setError('file_empty');
        }

        // get path and name
        if ($sourceFile instanceof UploadedFile) {
            $this->sourceName = $sourceFile->getClientOriginalName();
            $sourceFile = $sourceFile->getPathname();
        } else {
            $sourceFile = (string) $sourceFile;
            $this->sourceName = \basename($sourceFile);
        }

        // exist?
        if (!FileUtils::exists($sourceFile)) {
            return $this->setError('file_not_exist');
        }

        // create a temporary file
        if (null === $temp_name = FileUtils::tempfile('sql')) {
            return $this->setError('temp_file');
        }

        // same as current database?
        if (0 === \strcasecmp($sourceFile, $this->databaseName)) {
            return $this->setError('open_database');
        }

        try {
            // open archive
            if (!$this->openArchive($sourceFile)) {
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
                return $this->setError('empty_archive', ['%name%' => $this->sourceName]);
            }
        } finally {
            // close all
            $this->closeStream();
            $this->closeArchive();
            $this->closeDatabase();

            // move to new location
            $this->renameDatabase($temp_name);
        }

        // save date
        $this->updateValidity();

        return $this->results;
    }

    /**
     * Strip whitespace and convert to UTF-8 the given string.
     *
     * @param string $str the string to clean
     */
    private function clean(string $str): string
    {
        return \utf8_encode(\trim($str));
    }

    /**
     * Close the Zip archive.
     */
    private function closeArchive(): void
    {
        if (null !== $this->archive) {
            $this->archive->close();
        }
        $this->archive = null;
    }

    /**
     * Close the database.
     */
    private function closeDatabase(): void
    {
        if (null !== $this->database) {
            if ($this->results->isValid()) {
                $this->database->compact();
            }
            $this->database->close();
            $this->database = null;
        }
    }

    /**
     * Close the Zip archive entry stream.
     */
    private function closeStream(): void
    {
        if (\is_resource($this->stream)) {
            \fclose($this->stream);
        }
        $this->stream = false;
    }

    /**
     * Gets the date of the last import.
     */
    private function getLastImport(): ?\DateTimeInterface
    {
        return FileUtils::exists($this->databaseName) ? $this->application->getLastImport() : null;
    }

    /**
     * Open the Zip archive.
     */
    private function openArchive(string $sourceFile): bool
    {
        // open archive
        $this->archive = new \ZipArchive();
        if (true !== $this->archive->open($sourceFile)) {
            $this->setError('open_archive', ['%name%' => $this->sourceName]);

            return false;
        }

        // check if only 1 entry is present
        if (1 !== $this->archive->count()) {
            $this->setError('entry_not_one', ['%name%' => $this->sourceName]);
            $this->closeArchive();

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
        $streamName = (string) $this->archive->getNameIndex(0);
        if (false === $this->stream = $this->archive->getStream($streamName)) {
            $this->setError('open_stream', [
                '%name%' => $this->sourceName,
                '%streamName%' => $streamName,
            ]);
            $this->closeArchive();

            return false;
        }

        return true;
    }

    /**
     * Insert a city record to the database.
     */
    private function processCity(array $data): void
    {
        if (\count($data) > 9 && $this->database->insertCity([
                $data[1],               // id
                $data[4],               // zip code
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
        $filename = $this->dataDirectory . self::STATE_FILE;
        if (FileUtils::exists($filename) && false !== ($handle = \fopen($filename, 'r'))) {
            while (false !== ($data = \fgetcsv($handle, 0, ';'))) {
                if ($this->database->insertState($data)) {
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
        while ($process && false !== ($data = \fgetcsv($stream, 0, ';'))) {
            switch ((int) $data[0]) {
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
            if (0 === $this->results->getValids() % 50000) {
                $this->database->commitTransaction();
                $this->database->beginTransaction();
            }
        }

        // last commit
        $this->database->commitTransaction();

        //close
        $this->closeStream();

        return true;
    }

    /**
     * Insert a street record to the database.
     */
    private function processStreet(array $data): void
    {
        if (\count($data) > 6 && $this->database->insertStreet([
                $data[2],                         // city Id
                \ucfirst($this->clean($data[6])), // street name
        ])) {
            $this->results->addValidStreets();
        } else {
            $this->results->addErrorStreets();
        }
    }

    /**
     * Process the validity record.
     */
    private function processValidity(array $data): bool
    {
        $validity = null;
        if (\count($data) > 1) {
            $validity = \DateTime::createFromFormat(self::DATE_PATTERN, $data[1]);
            if ($validity instanceof \DateTime) {
                $validity = $validity->setTime(0, 0, 0, 0);
            }
        }

        if (!$validity instanceof \DateTimeInterface) {
            $this->setError('no_validity', ['%name%' => $this->sourceName]);

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
            $this->setError('rename_database');
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
     * Update the last imported date.
     */
    private function updateValidity(): void
    {
        if ($this->results->isValid() && null !== $validity = $this->results->getValidity()) {
            $this->application->setProperties([ApplicationServiceInterface::P_LAST_IMPORT => $validity]);
        }
    }
}