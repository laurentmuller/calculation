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
 * Service to import zip codes, cities and street names from Switzerland.
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
    private string $databaseName;
    private String $dataDirectory;
    private FormFactoryInterface $factory;

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
        $helper = new FormHelper($builder, 'import.');

        // file constraints
        $constraints = new File([
            'mimeTypes' => ['application/zip', 'application/x-zip-compressed'],
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
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
        $results = new SwissPostUpdateResult();

        // check source file
        if (null === $sourceFile || '' === $sourceFile) {
            return $results->setError($this->trans('import.error.file_empty'));
        }

        // get path and name
        if ($sourceFile instanceof UploadedFile) {
            $name = $sourceFile->getClientOriginalName();
            $sourceFile = $sourceFile->getPathname();
        } else {
            $sourceFile = (string) $sourceFile;
            $name = \basename($sourceFile);
        }

        // exist?
        if (!FileUtils::exists($sourceFile)) {
            return $results->setError($this->trans('import.error.file_not_exist'));
        }

        // create a temporary file
        if (null === $temp_name = FileUtils::tempfile('sql')) {
            return $results->setError($this->trans('import.error.temp_file'));
        }

        // same as current database?
        if (0 === \strcasecmp($sourceFile, $this->databaseName)) {
            return $results->setError($this->trans('import.error.open_database'));
        }

        $db = null;
        $archive = null;
        $stream = null;
        $opened = false;

        try {
            // open archive
            $archive = new \ZipArchive();
            if (true !== $opened = $archive->open($sourceFile)) {
                return $results->setError($this->trans('import.error.open_archive', ['%name%' => $name]));
            }

            // check if only 1 entry is present
            if (1 !== $archive->count()) {
                return $results->setError($this->trans('import.error.entry_not_one', ['%name%' => $name]));
            }

            // open entry
            $streamName = (string) $archive->getNameIndex(0);
            if (false === $stream = $archive->getStream($streamName)) {
                return $results->setError($this->trans('import.error.open_stream', [
                    '%name%' => $name,
                    '%streamName%' => $streamName,
                ]));
            }

            // open database
            $db = new SwissDatabase($temp_name);
            $db->beginTransaction();

            $lastImport = null;
            if (FileUtils::exists($this->databaseName)) {
                $lastImport = $this->application->getLastImport();
            }

            // states
            $this->processStates($db, $results);

            // process content
            $process = true;
            $total = $results->getValids();
            while ($process && false !== ($data = \fgetcsv($stream, 0, ';'))) {
                switch ((int) $data[0]) {
                    case self::REC_VALIDITY:
                        if (!$this->processValidity($data, $results, $lastImport, $name)) {
                            return $results;
                        }
                        break;

                    case self::REC_CITY:
                        $this->processCity($db, $data, $results);
                        break;

                    case self::REC_STREET:
                        $this->processStreet($db, $data, $results);
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

            // imported data?
            if (0 === $results->getValids()) {
                return $results->setError($this->trans('import.error.empty_archive', ['%name%' => $name]));
            }
        } finally {
            // close archive
            if (\is_resource($stream)) {
                \fclose($stream);
            }
            if ($opened) {
                $archive->close();
            }

            // close database
            if (null !== $db) {
                if ($results->isValid()) {
                    $db->compact();
                }
                $db->close();

                // move to new location
                if ($results->isValid() && !FileUtils::rename($temp_name, $this->databaseName, true)) {
                    $results->setError($this->trans('import.error.rename_database'));
                }
            }
        }

        // save date
        if ($results->isValid()) {
            $this->application->setProperties([ApplicationServiceInterface::P_LAST_IMPORT => $results->getValidity()]);
        }

        return $results;
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
     * Insert a city record to the database.
     */
    private function processCity(SwissDatabase $db, array $data, SwissPostUpdateResult $results): void
    {
        if (\count($data) > 9 && $db->insertCity([
                $data[1],               // id
                $data[4],               // zip code
                $this->clean($data[8]), // city name
                $data[9],               // state (canton)
        ])) {
            $results->addValidCities();
        } else {
            $results->addErrorCities();
        }
    }

    /**
     * Imports the states (canton) to the database.
     */
    private function processStates(SwissDatabase $db, SwissPostUpdateResult $results): void
    {
        $filename = $this->dataDirectory . self::STATE_FILE;
        if (FileUtils::exists($filename) && false !== ($handle = \fopen($filename, 'r'))) {
            while (false !== ($data = \fgetcsv($handle, 0, ';'))) {
                if ($db->insertState($data)) {
                    $results->addValidStates();
                } else {
                    $results->addErrorStates();
                }
            }
            \fclose($handle);
        }
    }

    /**
     * Insert a street record to the database.
     */
    private function processStreet(SwissDatabase $db, array $data, SwissPostUpdateResult $results): void
    {
        if (\count($data) > 6 && $db->insertStreet([
                $data[2],                         // city Id
                \ucfirst($this->clean($data[6])), // street name
        ])) {
            $results->addValidStreets();
        } else {
            $results->addErrorStreets();
        }
    }

    /**
     * Process the validity record.
     */
    private function processValidity(array $data, SwissPostUpdateResult $results, ?\DateTimeInterface $lastImport, string $name): bool
    {
        $validity = null;
        if (\count($data) > 1) {
            $validity = \DateTime::createFromFormat(self::DATE_PATTERN, $data[1]);
            if ($validity instanceof \DateTime) {
                $validity = $validity->setTime(0, 0, 0, 0);
            }
        }

        if (!$validity instanceof \DateTimeInterface) {
            $results->setError($this->trans('import.error.no_validity', ['%name%' => $name]));

            return false;
        }

        $results->setValidity($validity);
        if ($lastImport instanceof \DateTimeInterface && $validity <= $lastImport) {
            $results->setError($this->trans('import.error.validity_before', [
                '%validity%' => FormatUtils::formatDate($validity, \IntlDateFormatter::LONG),
                '%import%' => FormatUtils::formatDate($lastImport, \IntlDateFormatter::LONG),
                '%name%' => $name,
            ]));

            return false;
        }

        return true;
    }
}
