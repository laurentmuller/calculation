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
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to import OpenWeatherMap cities.
 *
 * @psalm-type OpenWeatherCityType = array{
 *     id: float,
 *     name: string,
 *     country: string,
 *     coord: array{
 *          lat: float,
 *          lon: float}
 *     }
 */
readonly class OpenWeatherCityUpdater
{
    use TranslatorTrait;

    /**
     * The import file extension.
     */
    private const FILE_EXTENSION = 'gz';

    public function __construct(
        private OpenWeatherService $service,
        private FormFactoryInterface $factory,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Create the import form.
     *
     * @return FormInterface<mixed>
     */
    public function createForm(): FormInterface
    {
        $builder = $this->factory->createBuilder();
        $helper = new FormHelper($builder, 'openweather.import.');
        $helper->field('file')
            ->addFileType(self::FILE_EXTENSION);

        return $helper->createForm();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Import data from the given file.
     *
     * @psalm-return array{result: bool, message: string, valid: int, error: int}
     */
    public function import(UploadedFile $file): array
    {
        $db = null;

        try {
            $cities = $this->getFileContent($file);
            if (false === $cities) {
                return $this->falseResult('swisspost.error.open_archive', [
                    '%name%' => $file->getClientOriginalName(),
                ]);
            }

            $temp_name = FileUtils::tempFile('sql');
            if (null === $temp_name) {
                return $this->falseResult('swisspost.error.temp_file');
            }

            $db = new OpenWeatherDatabase($temp_name);
            [$valid, $error] = $this->insertCities($db, $cities);
            $db->close();
            if (0 === $valid) {
                return $this->falseResult('openweather.error.empty_city');
            }
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
     * @psalm-return array{result: bool, message: string, valid: int, error: int}
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
     * @psalm-return OpenWeatherCityType[]|false
     */
    private function getFileContent(UploadedFile $file): array|false
    {
        if (!$file->isValid()) {
            return false;
        }
        $content = FileUtils::readFile($file);
        if ('' === $content) {
            return false;
        }
        $content = \gzdecode($content);
        if (false === $content) {
            return false;
        }

        try {
            /** @psalm-var OpenWeatherCityType[] */
            return StringUtils::decodeJson($content);
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @param OpenWeatherCityType[] $cities
     *
     * @psalm-return array{0: int, 1: int}
     */
    private function insertCities(OpenWeatherDatabase $db, array $cities): array
    {
        $valid = 0;
        $error = 0;
        $db->beginTransaction();
        foreach ($cities as $city) {
            if (!$db->insertCity(
                (int) $city['id'],
                $city['name'],
                $city['country'],
                $city['coord']['lat'],
                $city['coord']['lon']
            )) {
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
