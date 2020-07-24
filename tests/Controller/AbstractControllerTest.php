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
    protected static function addEntity(AbstractEntity $entity): void
    {
        if (null !== $entity) {
            /** @var EntityManager $em */
            $em = self::getManager();
            $em->persist($entity);
            $em->flush();
        }
    }

    protected function checkRoute(string $url, string $username, int $expected = Response::HTTP_OK, string $method = Request::METHOD_GET): void
    {
        $this->loginUserName($username);
        $this->client->request($method, $url);
        $this->checkResponse($url, $username, $expected);
    }

    protected static function deleteEntity(AbstractEntity $entity)
    {
        if (null !== $entity) {
            $em = self::getManager();
            $em->remove($entity);
            $em->flush();
        }

        return null;
    }

    protected static function getManager(): EntityManager
    {
        return self::$container->get('doctrine')->getManager();
    }
}
