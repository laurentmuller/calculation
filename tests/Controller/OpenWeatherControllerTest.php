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

namespace App\Tests\Controller;

use App\Model\HttpClientError;
use App\Service\OpenWeatherSearchService;
use App\Service\OpenWeatherService;
use App\Utils\FileUtils;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Component\HttpFoundation\Response;

class OpenWeatherControllerTest extends ControllerTestCase
{
    private const CITY_ID = 7286311;

    public static function getRoutes(): \Iterator
    {
        $users = [
            self::ROLE_USER,
            self::ROLE_ADMIN,
            self::ROLE_SUPER_ADMIN,
        ];

        $routes = [
            '/openweather/api/current',
            '/openweather/api/daily',
            '/openweather/api/forecast',
            '/openweather/api/onecall',
            '/openweather/api/search',
            '/openweather/current',
            '/openweather/search',
        ];
        foreach ($routes as $route) {
            foreach ($users as $user) {
                yield [$route, $user];
            }
        }
        foreach ($users as $user) {
            yield ['/openweather', $user, Response::HTTP_FOUND];
        }

        yield ['/openweather/import', self::ROLE_USER, Response::HTTP_FORBIDDEN];
        yield ['/openweather/import', self::ROLE_ADMIN];
        yield ['/openweather/import', self::ROLE_SUPER_ADMIN];
    }

    /**
     * @throws Exception
     */
    public function testApiCurrentException(): void
    {
        $this->checkRouteException('/openweather/api/current', 'current');
    }

    /**
     * @throws Exception
     */
    public function testApiCurrentFalse(): void
    {
        $this->checkRouteFalse('/openweather/api/current', 'current');
    }

    /**
     * @throws Exception
     */
    public function testApiDailyException(): void
    {
        $this->checkRouteException('/openweather/api/daily', 'daily');
    }

    /**
     * @throws Exception
     */
    public function testApiDailyFalse(): void
    {
        $this->checkRouteFalse('/openweather/api/daily', 'daily');
    }

    /**
     * @throws Exception
     */
    public function testApiForecastException(): void
    {
        $this->checkRouteException('/openweather/api/forecast', 'forecast');
    }

    /**
     * @throws Exception
     */
    public function testApiForecastFalse(): void
    {
        $this->checkRouteFalse('/openweather/api/forecast', 'forecast');
    }

    /**
     * @throws Exception
     */
    public function testApiOneCallException(): void
    {
        $this->checkRouteException('/openweather/api/onecall', 'oneCall');
    }

    /**
     * @throws Exception
     */
    public function testApiOneCallFalse(): void
    {
        $this->checkRouteFalse('/openweather/api/onecall', 'oneCall');
    }

    /**
     * @throws Exception
     */
    public function testApiSearchEmpty(): void
    {
        $service = $this->createMock(OpenWeatherSearchService::class);
        $service->method('search')
            ->willReturn([]);
        $this->setService(OpenWeatherSearchService::class, $service);
        $this->checkRoute(
            url: '/openweather/api/search',
            username: self::ROLE_USER,
        );
    }

    /**
     * @throws Exception
     */
    public function testApiSearchException(): void
    {
        $service = $this->createMock(OpenWeatherSearchService::class);
        $service->method('search')
            ->willThrowException(new \Exception());
        $this->setService(OpenWeatherSearchService::class, $service);
        $this->checkRoute(
            url: '/openweather/api/search',
            username: self::ROLE_USER,
        );
    }

    /**
     * @throws Exception
     */
    public function testApiSearchFound(): void
    {
        $city = [
            'id' => self::CITY_ID,
            'latitude' => 46.7318,
            'longitude' => 7.1875,
        ];
        $service = $this->createMock(OpenWeatherSearchService::class);
        $service->method('search')
            ->willReturn([$city]);
        $this->setService(OpenWeatherSearchService::class, $service);
        $this->checkRoute(
            url: '/openweather/api/search',
            username: self::ROLE_USER,
        );
    }

    /**
     * @throws Exception
     */
    public function testCurrent(): void
    {
        $data = [
            'current' => [],
        ];
        $service = $this->createMock(OpenWeatherService::class);
        $service->method('all')
            ->willReturn($data);
        $this->setService(OpenWeatherService::class, $service);

        $this->checkRoute(
            url: '/openweather/current',
            username: self::ROLE_USER,
        );
    }

    public function testImport(): void
    {
        $file = $this->getImportFile();

        try {
            $data = [
                'form[file]' => $file,
            ];
            $this->checkForm(
                uri: '/openweather/import',
                data: $data,
                followRedirect: false,
                disableReboot: true
            );
        } finally {
            FileUtils::remove($file);
        }
    }

    public function testSearchMultiple(): void
    {
        $data = [
            'form[query]' => 'paris',
            'form[units]' => 'metric',
            'form[limit]' => 15,
            'form[count]' => 5,
        ];

        $this->checkForm(
            uri: '/openweather/search',
            id: 'common.button_search',
            data: $data,
            followRedirect: false,
        );
    }

    public function testSearchOne(): void
    {
        $data = [
            'form[query]' => 'Le Mouret',
            'form[units]' => 'metric',
            'form[limit]' => 15,
            'form[count]' => 5,
        ];

        $this->checkForm(
            uri: '/openweather/search',
            id: 'common.button_search',
            data: $data,
        );
    }

    public function testWeatherWithId(): void
    {
        $this->checkRoute(
            url: '/openweather',
            username: self::ROLE_USER,
            expected: Response::HTTP_FOUND,
            parameters: ['id' => self::CITY_ID],
        );
    }

    /**
     * @throws Exception
     */
    private function checkRouteException(string $url, string $method): void
    {
        $service = $this->createMock(OpenWeatherService::class);
        $service->method($method)
            ->willThrowException(new \Exception());
        $this->setService(OpenWeatherService::class, $service);

        $this->checkRoute(
            url: $url,
            username: self::ROLE_USER,
        );
    }

    /**
     * @throws Exception
     */
    private function checkRouteFalse(string $url, string $method): void
    {
        $error = new HttpClientError(200, 'Fake');
        $service = $this->createMock(OpenWeatherService::class);
        $service->method($method)
            ->willReturn(false);
        $service->method('getLastError')
            ->willReturn($error);
        $this->setService(OpenWeatherService::class, $service);

        $this->checkRoute(
            url: $url,
            username: self::ROLE_USER,
        );
    }

    private function getImportFile(): string
    {
        $targetFile = FileUtils::tempFile(suffix: '.gz');
        self::assertIsString($targetFile);
        self::assertFileExists($targetFile);

        $originFile = __DIR__ . '/../Data/city.list.invalid.json.gz';
        self::assertFileExists($originFile);

        FileUtils::copy($originFile, $targetFile, true);

        return $targetFile;
    }
}
