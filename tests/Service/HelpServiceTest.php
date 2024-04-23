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

use App\Service\HelpService;
use App\Tests\ServiceTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

#[\PHPUnit\Framework\Attributes\CoversClass(HelpService::class)]
class HelpServiceTest extends KernelTestCase
{
    use ServiceTrait;

    private HelpService $service;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->service = $this->getService(HelpService::class);
    }

    public function testFindAction(): void
    {
        $action = $this->service->findAction('list_search');
        self::assertNotEmpty($action);
        $action = $this->service->findAction('fake_value_to_search');
        self::assertNull($action);
    }

    public function testHelp(): void
    {
        $help = $this->service->getHelp();
        self::assertNotEmpty($help);
    }
}
