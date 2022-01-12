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

namespace App\Tests\Controller;

use App\Entity\AbstractEntity;
use App\Tests\Web\AbstractAuthenticateWebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Unit test for {@link App\Controller\AboutController} class.
 * Asbtract unit test for controllers.
 *
 * @author Laurent Muller
 */
abstract class AbstractControllerTest extends AbstractAuthenticateWebTestCase
{
    /**
     * Gets the routes to test.
     *
     * Each entry must contains an URL, an user name, an expected result and the request method.
     *
     * @return array|\Generator
     *
     * @see AbstractControllerTest::testRoutes()
     */
    abstract public function getRoutes();

    /**
     * Checks the given route.
     *
     * @param string $url      the URL to be tested
     * @param string $username the user name to login
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
     *
     * @param AbstractEntity $entity the entity to add
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
     * @param string $username the user name to login
     * @param int    $expected the expected result
     * @param string $method   the request method
     */
    protected function checkRoute(string $url, string $username, int $expected = Response::HTTP_OK, string $method = Request::METHOD_GET): void
    {
        $isExcel = false !== \stripos($url, '/excel');
        $this->loginUserName($username);
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
     * @param AbstractEntity $entity the entity to delete
     *
     * @return mixed this function returns always null
     */
    protected function deleteEntity(?AbstractEntity $entity)
    {
        if (null !== $entity) {
            $em = self::getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }
}
