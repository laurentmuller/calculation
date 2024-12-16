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

namespace App\Tests\Parameter;

use App\Entity\GlobalProperty;
use App\Enums\EntityAction;
use App\Enums\StrengthLevel;
use App\Parameter\ApplicationParameters;
use App\Repository\GlobalPropertyRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class ApplicationParametersTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testSaveSuccess(): void
    {
        $cache = new ArrayAdapter();
        $manager = $this->createMockManager();
        $parameters = new ApplicationParameters($cache, $manager);
        $parameters->getCustomer()
            ->setAddress('fake');
        $parameters->getDate()
            ->setArchive();
        $parameters->getDefault()
            ->setMinMargin(1.0);
        $parameters->getDisplay()
            ->setEditAction(EntityAction::NONE);
        $parameters->getHomePage()
            ->setDarkNavigation(true);
        $parameters->getMessage()
            ->setIcon(false);
        $parameters->getOption()
            ->setPrintAddress(true);
        $parameters->getProduct()
            ->setQuantity(1.25);
        $parameters->getSecurity()
            ->setLevel(StrengthLevel::MEDIUM)
            ->setCompromised(true);

        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testWithProperty(): void
    {
        $cache = new ArrayAdapter();
        $property = GlobalProperty::instance('fake');
        $manager = $this->createMockManager($property);
        $parameters = new ApplicationParameters($cache, $manager);
        $parameters->getSecurity()
            ->setCompromised(true);
        $actual = $parameters->save();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    private function createMockManager(?GlobalProperty $property = null): MockObject&EntityManagerInterface
    {
        $repository = $this->createMock(GlobalPropertyRepository::class);
        $repository->method('findOneByName')
            ->willReturn($property);
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getRepository')
            ->willReturn($repository);

        return $manager;
    }
}
