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
use App\Interfaces\EntityInterface;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract unit test for controllers.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(AbstractController::class)]
abstract class AbstractControllerTestCase extends AbstractAuthenticateWebTestCase
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
    #[\PHPUnit\Framework\Attributes\DataProvider('getRoutes')]
    public function testRoutes(
        string $url,
        string $username = '',
        int $expected = Response::HTTP_OK,
        string $method = Request::METHOD_GET,
        bool $xmlHttpRequest = false
    ): void {
        $this->addEntities();
        $this->checkRoute($url, $username, $expected, $method, $xmlHttpRequest);
        if ($this->mustDeleteEntities()) {
            $this->deleteEntities();
        }
    }

    /**
     * This function is called before testing routes.
     */
    protected function addEntities(): void
    {
    }

    /**
     * Adds an entity to the database.
     *
     * @throws ORMException
     */
    protected function addEntity(?EntityInterface $entity): void
    {
        if ($entity instanceof EntityInterface) {
            $em = self::getManager();
            $em->persist($entity);
            $em->flush();
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
        bool $xmlHttpRequest = false
    ): void {
        $officeDocument = $this->isOfficeDocument($url);
        if ($this->mustLogin($username)) {
            $this->loginUsername($username);
        }
        if ($officeDocument) {
            \ob_start();
        }

        $server = $xmlHttpRequest ? ['HTTP_X-Requested-With' => 'XMLHttpRequest'] : [];
        $this->client->request(method: $method, uri: $url, server: $server);
        if ($officeDocument) {
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

    /**
     * Delete an entity from the database.
     *
     * @return null this function returns always null
     *
     * @throws ORMException
     */
    protected function deleteEntity(?EntityInterface $entity): null
    {
        if ($entity instanceof EntityInterface) {
            $em = self::getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }

    protected function isOfficeDocument(string $url): bool
    {
        return false !== \stripos($url, '/excel') || false !== \stripos($url, '/word');
    }

    protected function mustDeleteEntities(): bool
    {
        return false;
    }
}
