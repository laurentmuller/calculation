<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Tests\Controller;

use App\Entity\AbstractEntity;
use App\Tests\Web\AuthenticateWebTestCase;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Asbtract unit test for controllers.
 *
 * @author Laurent Muller
 */
abstract class AbstractControllerTest extends AuthenticateWebTestCase
{
    /**
     * Gets the route to test.
     *
     * Each entry must contains an URL, an user name and an expected result.
     */
    abstract public function getRoutes(): array;

    /**
     * @dataProvider getRoutes
     */
    public function testRoutes(string $url, string $username, int $expected = Response::HTTP_OK): void
    {
        $this->addEntities();
        $this->checkRoute($url, $username, $expected);
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
    protected function addEntity(AbstractEntity $entity): void
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
        $this->loginUserName($username);
        $this->client->request($method, $url);
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
    protected function deleteEntity(AbstractEntity $entity)
    {
        if (null !== $entity) {
            $em = self::getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }

    /**
     * Gets the entity manager.
     */
    protected static function getManager(): EntityManager
    {
        return self::$container->get('doctrine')->getManager();
    }
}
