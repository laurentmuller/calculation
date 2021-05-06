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

namespace App\Tests\Entity;

use App\Entity\AbstractEntity;
use App\Tests\DatabaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Abstract unit test for validate entity constraints.
 *
 * @author Laurent Muller
 */
abstract class AbstractEntityValidatorTest extends KernelTestCase
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

        /** @var ValidatorInterface $validator */
        $validator  = self::$container->get(ValidatorInterface::class);

        $this->validator = $validator;
    }

    protected function deleteEntity(AbstractEntity $object): void
    {
        $manager = $this->getManager();
        $manager->remove($object);
        $manager->flush();
    }

    protected function getManager(): EntityManager
    {
        /** @var ManagerRegistry $doctrine */
        $doctrine = self::$container->get('doctrine');

        /** @var EntityManager $manager */
        $manager = $doctrine->getManager();

        return $manager;
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
        $this->assertEquals($expected, $result->count());
    }
}
