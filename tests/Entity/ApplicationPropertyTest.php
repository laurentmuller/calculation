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

use App\Entity\ApplicationProperty;

final class ApplicationPropertyTest extends EntityValidatorTestCase
{
    public function testDuplicate(): void
    {
        $first = new ApplicationProperty();
        $first->withName('name')
            ->withValue('value');

        try {
            $this->saveEntity($first);
            $second = new ApplicationProperty();
            $second->withName('name')
                ->withValue('value');
            $results = $this->validate($second, 1);
            $this->validatePaths($results, 'name');
        } finally {
            $this->deleteEntity($first);
        }
    }

    public function testInstance(): void
    {
        $object = ApplicationProperty::instance('name');
        self::assertSame('name', $object->name);
    }

    public function testInvalidBoth(): void
    {
        $object = new ApplicationProperty();
        $results = $this->validate($object, 2);
        $this->validatePaths($results, 'name', 'value');
    }

    public function testInvalidName(): void
    {
        $object = new ApplicationProperty();
        $object->withValue('value');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'name');
    }

    public function testInvalidValue(): void
    {
        $object = new ApplicationProperty();
        $object->withName('name');
        $results = $this->validate($object, 1);
        $this->validatePaths($results, 'value');
    }

    public function testNotDuplicate(): void
    {
        $first = new ApplicationProperty();
        $first->withName('name')
            ->withValue('value');

        try {
            $this->saveEntity($first);
            $second = new ApplicationProperty();
            $second->withName('name2')
                ->withValue('value');
            $this->validate($second);
        } finally {
            $this->deleteEntity($first);
        }
    }
}
