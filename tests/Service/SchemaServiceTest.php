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

namespace App\Tests\Service;

use App\Service\SchemaService;
use App\Tests\DatabaseTrait;
use App\Tests\KernelServiceTestCase;

class SchemaServiceTest extends KernelServiceTestCase
{
    use DatabaseTrait;

    private SchemaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->getService(SchemaService::class);
    }

    public function testGetTable(): void
    {
        $actual = $this->service->getTable('sy_Group');
        self::assertSame('sy_Group', $actual['name']);
    }

    public function testGetTables(): void
    {
        $actual = $this->service->getTables();
        self::assertNotEmpty($actual);
    }

    public function testTableExists(): void
    {
        $actual = $this->service->tableExists('sy_Group');
        self::assertTrue($actual);
        $actual = $this->service->tableExists('fake');
        self::assertFalse($actual);
    }
}
