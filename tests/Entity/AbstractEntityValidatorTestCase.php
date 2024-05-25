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

namespace App\Tests\Entity;

use App\Interfaces\EntityInterface;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Unit test for validate {@link EntityInterface} class.
 */
abstract class AbstractEntityValidatorTestCase extends KernelServiceTestCase
{
    use DatabaseTrait;

    protected ?ValidatorInterface $validator = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = $this->getService(ValidatorInterface::class);
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteEntity(EntityInterface $entity): void
    {
        $manager = $this->getManager();
        $manager->remove($entity);
        $manager->flush();
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function saveEntity(EntityInterface $object): void
    {
        $manager = $this->getManager();
        $manager->persist($object);
        $manager->flush();
    }

    protected function validate(mixed $object, int $expected = 0): ConstraintViolationListInterface
    {
        self::assertNotNull($this->validator);
        $result = $this->validator->validate($object);
        self::assertCount($expected, $result);

        return $result;
    }

    protected function validatePaths(ConstraintViolationListInterface $results, string ...$paths): void
    {
        $sources = [];
        foreach ($results as $result) {
            $sources[] = $result->getPropertyPath();
        }
        $diff = \array_diff($paths, $sources);
        if ([] !== $diff) {
            self::fail(\sprintf('Unable to find constraint violation for path: "%s".', \implode('", "', $diff)));
        }
    }
}
