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

namespace App\Tests\Service;

use App\Interfaces\ApplicationServiceInterface;
use App\Service\ApplicationService;
use App\Tests\DatabaseTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Test for the application service.
 *
 * @author Laurent Muller
 */
class ApplicationServiceTest extends KernelTestCase implements ApplicationServiceInterface
{
    use DatabaseTrait;

    /*
     * the debug mode
     */
    protected bool $debug = false;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $this->debug = self::$kernel->isDebug();
    }

    public function testService(): void
    {
        /** @var ApplicationService $service */
        $service = static::getContainer()->get(ApplicationService::class);

        $service->setProperties([
            self::P_CUSTOMER_NAME => 'customer_name',
            self::P_CUSTOMER_URL => 'customer_url',
        ]);
        $this->assertEquals('customer_name', $service->getCustomerName());
        $this->assertEquals('customer_url', $service->getCustomerUrl());

        $this->assertNull($service->getLastImport());
        $this->assertNull($service->getUpdateCalculations());
        $this->assertNull($service->getUpdateProducts());

        $this->assertNull($service->getDefaultState());
        $this->assertEquals(0, $service->getDefaultStateId());

        $this->assertNull($service->getDefaultCategory());
        $this->assertEquals(0, $service->getDefaultCategoryId());

        $this->assertEquals(3.0, $service->getMinMargin());
        $this->assertEquals(-1, $service->getMinStrength());
        $this->assertEquals(4000, $service->getMessageTimeout());
    }

    /**
     * @param mixed $value
     */
    protected function echo(string $name, $value, bool $newLine = false): void
    {
        if ($this->debug) {
            $format = "\n%-15s: %s" . ($newLine ? "\n" : '');
            \printf($format, \htmlspecialchars($name), $value);
        }
    }
}
