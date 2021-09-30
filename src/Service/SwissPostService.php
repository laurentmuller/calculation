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
use App\Interfaces\ApplicationServiceInterface;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to import zip code and city from Switzerland.
 *
 * @author Laurent Muller
 */
class SwissPostService
{
    use TranslatorTrait;

    /**
     * The database name.
     */
    public const DATABASE_NAME = 'swiss.sqlite';

    /**
     * The relative path to data.
     */
    private const DATA_PATH = '/resources/data/';

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

    private string $dataDirectory;

    /**
     * The source file to import.
     */
    private ?string $sourceFile = null;

    /**
     * The original import (uploaded) file name.
     */
    private ?string $sourceName = null;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, TranslatorInterface $tranlator, ApplicationService $application)
    {
        $this->translator = $tranlator;
        $this->application = $application;
        $this->dataDirectory = $kernel->getProjectDir() . self::DATA_PATH;
    }

    /**
     * Finds cities by name.
     *
     * @param string $name  the name to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findCity(string $name, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findCity($name, $limit);
        $db->close();

        return $result;
    }

    /**
     * Finds street by name.
     *
     * @param string $name  the name to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findStreet(string $name, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findStreet($name, $limit);
        $db->close();

        return $result;
    }

    /**
     * Finds cities by zip code.
     *
     * @param string $zip   the zip code to search for
     * @param int    $limit the maximum number of rows to return
     *
     * @return array an array, maybe empty, of matching cities
     */
    public function findZip(string $zip, int $limit = 25): array
    {
        $db = $this->getDatabase(true);
        $result = $db->findZip($zip, $limit);
        $db->close();

        return $result;
    }

    /**
     * Gets the database.
     *
     * @param bool $readonly true open the database for reading only
     *
     * @return SwissDatabase the database
     */
    public function getDatabase(bool $readonly = false): SwissDatabase
    {
        return new SwissDatabase($this->getDatabaseName(), $readonly);
    }

    /**
     * Gets the database file name.
     */
    public function getDatabaseName(): string
    {
        return $this->dataDirectory . self::DATABASE_NAME;
    }

    /**
     * Gets the data directory.
     *
     * @return string
     */
    public function getDataDirectory(): ?string
    {
        return $this->dataDirectory;
    }

    /**
     * Gets the source file to import.
     *
     * @return string
     */
    public function getSourceFile(): ?string
    {
        return $this->sourceFile;
    }

    /**
     * Gets the source file name to import.
     *
     * @return string
     */
    public function getSourceName(): ?string
    {
        return $this->sourceName;
    }

    /**
     * Import data from the source file.
     *
     * @return array the import result
     */
    public function import(): array
    {
        // source file?
        if (!$this->sourceFile || !FileUtils::exists($this->sourceFile)) {
            return [
                'valid' => false,
                'message' => $this->trans('import.error.source_file'),
            ];
        }

        // create a temporary file
        if (null === $temp_name = FileUtils::tempfile('sql')) {
            return [
                'valid' => false,
                'message' => $this->trans('import.error.temp_file'),
            ];
        }

        // file name
        $name = $this->sourceName ?? \basename($this->sourceFile);

        // same as current database?
        if (0 === \strcasecmp($this->sourceFile, $this->getDatabaseName())) {
            return [
                'valid' => false,
                'message' => $this->trans('import.error.open_database'),
            ];
        }

        $db = null;
        $archive = null;
        $stream = null;
        $opened = false;
        $valid = false;

        try {
            // open archive
            $archive = new \ZipArchive();
            if (true !== $opened = $archive->open($this->sourceFile)) {
                $opened = false;

                return [
                    'valid' => false,
                    'message' => $this->trans('import.error.open_archive', ['%name%' => $name]),
                ];
            }

            // check file
            if (1 !== $archive->count()) {
                return [
                    'valid' => false,
                    'message' => $this->trans('import.error.entry_not_one', ['%name%' => $name]),
                ];
            }

            // check entry
            $streamName = (string) $archive->getNameIndex(0);

            // open entry
            if (false === $stream = $archive->getStream($streamName)) {
                return [
                    'valid' => false,
                    'message' => $this->trans('import.error.open_stream', [
                        '%name%' => $name,
                        '%streamName%' => $streamName,
                    ]),
                ];
            }

            // open database
            $db = new SwissDatabase($temp_name);
            $db->beginTransaction();

            /** @var \DateTime|null $validity */
            $validity = null;

            /** @var \DateTime $lastImport */
            $lastImport = $this->application->getLastImport();

            // database exist?
            if (!FileUtils::exists($this->getDatabaseName())) {
                $lastImport = null;
            }

            $validCities = 0;
            $errorCities = 0;
            $validStreets = 0;
            $errorStreets = 0;

            // states
            [$validStates, $errorStates] = $this->importStates($db);

            // process content
            $process = true;
            $total = $validStates;
            while ($process && false !== ($data = \fgetcsv($stream, 0, ';'))) {
                switch ((int) $data[0]) {
                    case self::REC_VALIDITY:
                        if (($validity = $this->processValidity($data)) === null) {
                            return [
                                'valid' => false,
                                'message' => $this->trans('import.error.no_validity', ['%name%' => $name]),
                            ];
                        }

                        // check
                        if ($lastImport && $validity <= $lastImport) {
                            $params = [
                                '%validity%' => FormatUtils::formatDate($validity, \IntlDateFormatter::LONG),
                                '%import%' => FormatUtils::formatDate($lastImport, \IntlDateFormatter::LONG),
                                '%name%' => $name,
                            ];

                            return [
                                'valid' => false,
                                'message' => $this->trans('import.error.validity_before', $params),
                            ];
                        }
                        break;

                    case self::REC_CITY:
                        if ($this->insertCity($db, $data)) {
                            ++$validCities;
                        } else {
                            ++$errorCities;
                        }
                        break;

                    case self::REC_STREET:
                        if ($this->insertStreet($db, $data)) {
                            ++$validStreets;
                        } else {
                            ++$errorStreets;
                        }
                        break;

                    case self::REC_STOP_PROCESS:
                        $process = false;
                        break;
                }

                // commit
                if (0 === ++$total % 50000) {
                    $db->commitTransaction();
                    $db->beginTransaction();
                }
            }

            // last commit
            $db->commitTransaction();

            // validity?
            if (null === $validity) {
                return [
                    'valid' => false,
                    'message' => $this->trans('import.error.no_validity', ['%name%' => $name]),
                ];
            }

            // imported data?
            if (0 === $validCities || 0 === $validStreets) {
                return [
                    'valid' => false,
                    'message' => $this->trans('import.error.empty_archive', ['%name%' => $name]),
                ];
            }

            // save date
            $valid = true;
            $this->application->setProperties([ApplicationServiceInterface::P_LAST_IMPORT => $validity]);

            // OK
            return [
                'valid' => $valid,
                'validity' => $validity,
                'states' => [
                    'valid' => $validStates,
                    'error' => $errorStates,
                ],
                'cities' => [
                    'valid' => $validCities,
                    'error' => $errorCities,
                ],
                'streets' => [
                    'valid' => $validStreets,
                    'error' => $errorStreets,
                ],
            ];
        } finally {
            // close archive
            if (\is_resource($stream)) {
                \fclose($stream);
            }
            if ($opened) {
                $archive->close();
            }

            //close database
            if (null !== $db) {
                if ($valid) {
                    $db->compact();
                }
                $db->close();

                // move to new location
                if ($valid) {
                    FileUtils::rename($temp_name, $this->getDatabaseName(), true);
                }
            }
        }
    }

    /**
     * Sets the data directory.
     */
    public function setDataDirectory(string $dataDirectory): self
    {
        $this->dataDirectory = $dataDirectory;

        return $this;
    }

    /**
     * Sets the source file to import.
     *
     * @param string|UploadedFile $sourceFile the source file to import
     */
    public function setSourceFile($sourceFile): self
    {
        if ($sourceFile instanceof UploadedFile) {
            $this->sourceFile = $sourceFile->getPathname();
            $this->sourceName = $sourceFile->getClientOriginalName();
        } else {
            $this->sourceFile = (string) $sourceFile;
            $this->sourceName = \basename($this->sourceFile);
        }

        return $this;
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
     * Imports the states (canton) to the database.
     *
     * @param SwissDatabase $db the database
     *
     * @return array an array with the number of inserted states and the number of errors
     */
    private function importStates(SwissDatabase $db): array
    {
        $valid = 0;
        $error = 0;
        $filename = $this->dataDirectory . self::STATE_FILE;

        if (FileUtils::exists($filename) && false !== ($handle = \fopen($filename, 'r'))) {
            while (false !== ($data = \fgetcsv($handle, 0, ';'))) {
                if ($db->insertState($data)) {
                    ++$valid;
                } else {
                    ++$error;
                }
            }
            \fclose($handle);
        }

        return [$valid, $error];
    }

    /**
     * Insert a city record to the database.
     *
     * @param SwissDatabase $db   the database to insert into
     * @param array         $data the data to process
     *
     * @return bool true if success
     */
    private function insertCity(SwissDatabase $db, array $data): bool
    {
        if (\count($data) > 9) {
            return $db->insertCity([
                $data[1],               // id
                $data[4],               // zip code
                $this->clean($data[8]), // city name
                $data[9],               // state (canton)
            ]);
        }

        return false;
    }

    /**
     * Insert a street record to the database.
     *
     * @param SwissDatabase $db   the database to insert into
     * @param array         $data the data to process
     *
     * @return bool true if success
     */
    private function insertStreet(SwissDatabase $db, array $data): bool
    {
        if (\count($data) > 6) {
            return $db->insertStreet([
                $data[2],                         // city Id
                \ucfirst($this->clean($data[6])), // street name
            ]);
        }

        return false;
    }

    /**
     * Process the validity record.
     *
     * @param array $data the data to process
     *
     * @return \DateTimeInterface|null the validity date or null on failure
     */
    private function processValidity(array $data): ?\DateTimeInterface
    {
        if (\count($data) > 1 && false !== $date = \DateTime::createFromFormat(self::DATE_PATTERN, $data[1])) {
            return $date->setTime(0, 0, 0, 0);
        }

        return null;
    }
}
