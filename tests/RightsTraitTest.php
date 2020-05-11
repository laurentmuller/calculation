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

namespace App\Tests;

use App\Interfaces\EntityVoterInterface;
use App\Security\EntityVoter;
use App\Traits\RightsTrait;
use PHPUnit\Framework\TestCase;

/***
 * Unit test for RightsTrait.
 *
 * @author Laurent Muller
 * @see RightsTrait
 */
class RightsTraitTest extends TestCase implements EntityVoterInterface
{
    use RightsTrait;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->rights = null;
    }

    public function getEntities(): array
    {
        return [EntityVoter::ENTITIES];
    }

    public function getMaskAttributes(): array
    {
        return [EntityVoter::MASK_ATTRIBUTES];
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetAdd(string $entity): void
    {
        $this->checkAttribute($entity, self::ATTRIBUTE_ADD);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetDelete(string $entity): void
    {
        $this->checkAttribute($entity, self::ATTRIBUTE_DELETE);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEdit(string $entity): void
    {
        $this->checkAttribute($entity, self::ATTRIBUTE_EDIT);
    }

    /**
     * @dataProvider getEntities
     */
    public function testGetEmpty(string $entity): void
    {
        $this->assertEmpty($this->__get($entity));
    }

    public function testIsNotSet(): void
    {
        $this->assertFalse($this->__isset('UnknowClass'));
    }

    /**
     * @dataProvider getEntities
     */
    public function testIsSet(string $entity): void
    {
        $this->assertTrue($this->__isset($entity));
    }

    private function checkAttribute(string $entity, string $key): void
    {
        $attribute = $this->getAttribute($key);
        $rights = [$key => $attribute];
        $this->__set($entity, $rights);
        $value = $this->__get($entity);
        $this->assertSame($rights, $value);
    }

    private function getAttribute(string $key): int
    {
        if (\array_key_exists($key, EntityVoter::MASK_ATTRIBUTES)) {
            return EntityVoter::MASK_ATTRIBUTES[$key];
        }

        return  EntityVoter::INVALID_VALUE;
    }
}
