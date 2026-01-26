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

use App\Tests\Web\AuthenticateWebTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract unit test case for controllers.
 */
abstract class ControllerTestCase extends AuthenticateWebTestCase
{
    protected const array DEFAULT_USERS = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
        self::ROLE_SUPER_ADMIN,
    ];

    /**
     * Gets the routes to test.
     *
     * Each entry must contain a URL, a username, an optional expected result, request method and XML http request.
     */
    abstract public static function getRoutes(): \Generator;

    /**
     * Checks the given route.
     *
     * @param string $url            the URL to be tested
     * @param string $username       the username to log in
     * @param int    $expected       the expected result
     * @param string $method         the request method
     * @param bool   $xmlHttpRequest true if XMLHttpRequest
     */
    #[DataProvider('getRoutes')]
    public function testRoutes(
        string $url,
        string $username = '',
        int $expected = Response::HTTP_OK,
        string $method = Request::METHOD_GET,
        bool $xmlHttpRequest = false
    ): void {
        try {
            $this->addEntities();
            $this->checkRoute($url, $username, $expected, $method, $xmlHttpRequest);
        } finally {
            if ($this->mustDeleteEntities()) {
                $this->deleteEntities();
            }
        }
    }

    /**
     * This function is called before testing routes.
     */
    protected function addEntities(): void
    {
    }

    protected function checkForm(
        string $uri,
        string $id = 'common.button_ok',
        array $data = [],
        string $userName = self::ROLE_ADMIN,
        string $method = Request::METHOD_POST,
        bool $followRedirect = true,
        bool $disableReboot = false
    ): void {
        try {
            if ($disableReboot) {
                $this->client->disableReboot();
            }
            if ('' !== $userName) {
                $this->loginUsername($userName);
            }
            $this->client->request($method, $uri);
            $button = $this->getService(TranslatorInterface::class)
                ->trans($id);
            $this->client->submitForm($button, $data);
            if ($followRedirect) {
                $this->client->followRedirect();
            }
            self::assertResponseIsSuccessful();
        } finally {
            if ($disableReboot) {
                $this->client->enableReboot();
            }
        }
    }

    /**
     * Checks the given route.
     *
     * @param string $url            the URL to be tested
     * @param string $username       the username to log in or empty string if none
     * @param int    $expected       the expected result
     * @param string $method         the request method
     * @param bool   $xmlHttpRequest true if XMLHttpRequest
     */
    protected function checkRoute(
        string $url,
        string $username = '',
        int $expected = Response::HTTP_OK,
        string $method = Request::METHOD_GET,
        bool $xmlHttpRequest = false,
        array $parameters = [],
        ?string $content = null
    ): string|false {
        if ($this->mustLogin($username)) {
            $this->loginUsername($username);
        }
        $outputBuffer = $this->isOutputBuffer($url);
        if ($outputBuffer) {
            \ob_start();
        }

        if ($xmlHttpRequest) {
            $this->client->xmlHttpRequest(
                method: $method,
                uri: $url,
                parameters: $parameters,
                content: $content
            );
        } else {
            $this->client->request(
                method: $method,
                uri: $url,
                parameters: $parameters,
                content: $content
            );
        }
        if ($outputBuffer) {
            \ob_get_clean();
        }

        return $this->checkResponse($url, $username, $expected);
    }

    /**
     * Delete entities from the database.
     */
    protected function deleteEntities(): void
    {
    }

    protected function isOutputBuffer(string $url): bool
    {
        return false !== \stripos($url, '/excel')
            || false !== \stripos($url, '/word')
            || false !== \stripos($url, '/csv');
    }

    protected function mustDeleteEntities(): bool
    {
        return false;
    }
}
