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
use Symfony\Component\DomCrawler\Crawler;
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
        self::ROLE_WEBMASTER,
    ];

    protected bool $mustCheckButtons = false;

    /**
     * Gets the routes to test.
     *
     * Each entry must contain a URL, a username, an optional expected result, request method, and XML http request.
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

    protected function checkButtons(Crawler $crawler, string $url): void
    {
        $buttons = $crawler->filter('button');
        if (0 === $buttons->count()) {
            return;
        }

        foreach ($buttons as $button) {
            if (!$button->hasAttributes()) {
                self::fail(\sprintf('No button attribute found in "%s".', $url));
            }
            $type = $button->attributes->getNamedItem('type');
            if (!$type instanceof \DOMNode) {
                self::fail(\sprintf('No button type attribute found in "%s".', $url));
            }
            $value = $type->nodeValue;
            if (!\is_string($value)) {
                self::fail(\sprintf('No button value found in "%s".', $url));
            }
            if (!\in_array(\strtolower($value), ['submit', 'reset', 'button'], true)) {
                self::fail(\sprintf('Invalid button type "%s" found in "%s".', $value, $url));
            }
        }
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

            $this->client->catchExceptions(false);
            $this->client->request($method, $uri);
            self::assertResponseIsSuccessful();

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
            $crawler = $this->client->xmlHttpRequest(
                method: $method,
                uri: $url,
                parameters: $parameters,
                content: $content
            );
        } else {
            $crawler = $this->client->request(
                method: $method,
                uri: $url,
                parameters: $parameters,
                content: $content
            );
        }
        if ($outputBuffer) {
            \ob_get_clean();
        }

        if ($this->mustCheckButtons) {
            $this->checkButtons($crawler, $url);
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
