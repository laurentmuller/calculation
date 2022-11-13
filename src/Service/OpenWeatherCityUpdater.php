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

use App\Database\OpenWeatherDatabase;
use App\Form\FormHelper;
use App\Traits\TranslatorTrait;
use App\Util\FileUtils;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to import OpenWeatherMap cities.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class OpenWeatherCityUpdater
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(
        private readonly OpenWeatherService $service,
        private readonly FormFactoryInterface $factory,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Create the import form.
     */
    public function createForm(): FormInterface
    {
        $constraint = new File([
            'mimeTypes' => 'application/gzip',
            'mimeTypesMessage' => $this->trans('openweather.error.mime_type'),
        ]);
        $builder = $this->factory->createBuilder();
        $helper = new FormHelper($builder, 'openweather.import.');

        return $helper->field('file')
            ->updateAttribute('accept', 'application/x-gzip')
            ->constraints($constraint)
            ->addFileType()
            ->createForm();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Import data from the given file.
     *
     * @return array{result: bool, message: string, valid: int, error: int}
     */
    public function import(UploadedFile $file): array
    {
        $db = null;

        try {
            // create temp file
            if (null === $temp_name = FileUtils::tempfile('sql')) {
                return $this->falseResult('swisspost.error.temp_file');
            }

            // get cities
            if (!$cities = $this->getFileContent($file)) {
                return $this->falseResult('swisspost.error.open_archive', [
                        '%name%' => $file->getClientOriginalName(),
                    ]);
            }

            // insert cities
            $db = new OpenWeatherDatabase($temp_name);
            [$valid, $error] = $this->insertCities($db, $cities);
            $db->close();

            // cities?
            if (0 === $valid) {
                return $this->falseResult('openweather.error.empty_city');
            }

            // move database
            if (!FileUtils::rename($temp_name, $this->service->getDatabaseName(), true)) {
                return $this->falseResult('swisspost.error.rename_database');
            }

            return [
                'result' => true,
                'valid' => $valid,
                'error' => $error,
                'message' => $this->trans('openweather.result.success'),
            ];
        } finally {
            FileUtils::remove($file);
            $db?->close();
        }
    }

    /**
     * @return array{result: bool, message: string, valid: int, error: int}
     */
    private function falseResult(string $message, array $parameters = []): array
    {
        return [
            'result' => false,
            'valid' => 0,
            'error' => 0,
            'message' => $this->trans($message, $parameters),
        ];
    }

    /**
     * @return array<array{
     *     id: float,
     *     name: string,
     *     country: string,
     *     coord: array{
     *          lat: float,
     *          lon: float}}>|false
     */
    private function getFileContent(UploadedFile $file): array|false
    {
        if (!$file->isValid()) {
            return false;
        }
        if (!$filename = $file->getRealPath()) {
            return false;
        }
        if (!$content = \file_get_contents($filename)) {
            return false;
        }
        if (!$content = \gzdecode($content)) {
            return false;
        }
        /**
         * @var array<array{
         *     id: float,
         *     name: string,
         *     country: string,
         *     coord: array{
         *          lat: float,
         *          lon: float}}>|null $result
         */
        $result = \json_decode($content, true);

        return \is_array($result) ? $result : false;
    }

    /**
     * @param array<array{
     *     id: float,
     *     name: string,
     *     country: string,
     *     coord: array{
     *          lat: float,
     *          lon: float}}> $cities
     *
     * @return int[]
     */
    private function insertCities(OpenWeatherDatabase $db, array $cities): array
    {
        $valid = 0;
        $error = 0;
        $db->beginTransaction();
        foreach ($cities as $city) {
            if (!$db->insertCity((int) $city['id'], $city['name'], $city['country'], $city['coord']['lat'], $city['coord']['lon'])) {
                ++$error;
            } elseif (0 === ++$valid % 50_000) {
                $db->commitTransaction();
                $db->beginTransaction();
            }
        }
        $db->commitTransaction();
        $db->compact();

        return [$valid, $error];
    }
}
