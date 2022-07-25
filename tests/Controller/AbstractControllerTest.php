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

use App\Entity\AbstractEntity;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Generator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Abstract unit test for controllers.
 */
abstract class AbstractControllerTest extends AbstractAuthenticateWebTestCase
{
    /**
     * Gets the routes to test.
     *
     * Each entry must contain a URL, a username, an optional expected result and the request method.
     */
    abstract public function getRoutes(): array|Generator;

    /**
     * Checks the given route.
     *
     * @param string $url      the URL to be tested
     * @param string $username the username to log in
     * @param int    $expected the expected result
     * @param string $method   the request method
     *
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK, string $method = Request::METHOD_GET): void
    {
        $this->addEntities();
        $this->checkRoute($url, $username, $expected, $method);
    }

    /**
     * This function is called before testing routes.
     */
    protected function addEntities(): void
    {
    }

    /**
     * Adds an entity to the database.
     */
    protected function addEntity(?AbstractEntity $entity): void
    {
        if (null !== $entity) {
            $em = self::getManager();
            $em->persist($entity);
            $em->flush();
        }
    }

    /**
     * Checks the given route.
     *
     * @param string $url      the URL to be tested
     * @param string $username the username to log in or empty('') if none
     * @param int    $expected the expected result
     * @param string $method   the request method
     */
    protected function checkRoute(string $url, string $username, int $expected = Response::HTTP_OK, string $method = Request::METHOD_GET): void
    {
        $isExcel = false !== \stripos($url, '/excel');
        if (!empty($username)) {
            $this->loginUserName($username);
        }
        if ($isExcel) {
            \ob_start();
        }
        $this->client->request($method, $url);
        if ($isExcel) {
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
     */
    protected function deleteEntity(?AbstractEntity $entity): mixed
    {
        if (null !== $entity) {
            $em = self::getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }
}
