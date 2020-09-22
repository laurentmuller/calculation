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

namespace App\Tests\Entity;

use App\Entity\AbstractEntity;
use App\Tests\DatabaseTrait;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Abstract unit test for validate entity constraints.
 *
 * @author Laurent Muller
 */
abstract class EntityValidatorTest extends KernelTestCase
{
    use DatabaseTrait;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        static::bootKernel();
        $this->validator = self::$container->get(ValidatorInterface::class);
    }

    protected function deleteEntity(AbstractEntity $object): void
    {
        $manager = $this->getManager();
        $manager->remove($object);
        $manager->flush();
    }

    protected function getManager(): ObjectManager
    {
        return self::$container->get('doctrine')->getManager();
    }

    protected function saveEntity(AbstractEntity $object): void
    {
        $manager = $this->getManager();
        $manager->persist($object);
        $manager->flush();
    }

    /**
     * Validates the given value.
     *
     * @param mixed $object   the value to validate
     * @param int   $expected the number of expected errors
     */
    protected function validate($object, int $expected): void
    {
        $result = $this->validator->validate($object);
        $this->assertSame($expected, $result->count());
    }
}
