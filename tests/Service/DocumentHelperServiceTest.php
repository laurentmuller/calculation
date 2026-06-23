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

use App\Parameter\UserParameters;
use App\Service\DocumentHelperService;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

final class DocumentHelperServiceTest extends TestCase
{
    use TranslatorMockTrait;

    public function testService(): void
    {
        $translator = $this->createMockTranslator();
        $parameters = self::createStub(UserParameters::class);
        $security = self::createStub(Security::class);
        $service = new DocumentHelperService($translator, $parameters, $security);

        self::assertSame($translator, $service->getTranslator());

        $actual = $service->getCustomer();
        self::assertNull($actual->getName());

        $actual = $service->getMinMargin();
        self::assertSame(0.0, $actual);

        $actual = $service->getUserIdentifier();
        self::assertNull($actual);
    }
}
