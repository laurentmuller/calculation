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

use App\Controller\AbstractController;
use App\Tests\Web\AuthenticateWebTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract unit test for controllers.
 */
#[CoversClass(AbstractController::class)]
abstract class ControllerTestCase extends AuthenticateWebTestCase
{
    /**
     * Gets the routes to test.
     *
     * Each entry must contain a URL, a username, an optional expected result, request method and XML http request.
     */
    abstract public static function getRoutes(): \Iterator;

    /**
     * Checks the given route.
     *
     * @param string $url      the URL to be tested
     * @param string $username the username to log in
     * @param int    $expected the expected result
     * @param string $method   the request method
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
    ): void {
        if ($this->mustLogin($username)) {
            $this->loginUsername($username);
        }
        $outputBuffer = $this->isOutputBuffer($url);
        if ($outputBuffer) {
            \ob_start();
        }

        $server = $xmlHttpRequest ? ['HTTP_X-Requested-With' => 'XMLHttpRequest'] : [];
        $this->client->request(
            method: $method,
            uri: $url,
            parameters: $parameters,
            server: $server,
            content: $content
        );
        if ($outputBuffer) {
            \ob_get_clean();
        }
        $this->checkResponse($url, $username, $expected);
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
