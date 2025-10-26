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

namespace App\Tests\Form\DataTransformer;

use App\Entity\Group;
use App\Repository\GroupRepository;
use App\Tests\Entity\IdTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;

/**
 * @template TValue of Group|int
 * @template TTransformedValue of Group|int
 */
abstract class EntityTransformerTestCase extends TestCase
{
    use IdTrait;

    /**
     * @throws \ReflectionException
     */
    protected function createGroup(): Group
    {
        return self::setId(new Group());
    }

    protected function createRepository(?Group $group = null): GroupRepository
    {
        $repository = $this->createMock(GroupRepository::class);
        $repository->method('find')
            ->willReturn($group);
        $repository->method('getClassName')
            ->willReturn(Group::class);

        return $repository;
    }

    /**
     * @return DataTransformerInterface<TValue, TTransformedValue>
     */
    abstract protected function createTransformer(?Group $group = null): DataTransformerInterface;
}
