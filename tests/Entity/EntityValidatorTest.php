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
        $this->assertEquals($expected, $result->count());
    }
}
