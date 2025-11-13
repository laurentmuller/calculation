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

namespace App\Tests\Word;

use App\Controller\AbstractController;
use App\Model\CustomerInformation;
use App\Service\ApplicationService;
use App\Service\UserService;
use App\Tests\TranslatorMockTrait;
use App\Word\AbstractWordDocument;
use App\Word\HtmlDocument;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class AbstractWordDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testDefault(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        $doc->setTranslatedTitle('id');
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    public function testWithCustomer(): void
    {
        $cs = $this->createCustomerInformation();
        $controller = $this->createMockController($cs);
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }
        };
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    public function testWithEmptyValues(): void
    {
        $cs = new CustomerInformation();
        $controller = $this->createMockController($cs);
        $this->render($controller);
    }

    public function testWithoutEmail(): void
    {
        $cs = $this->createCustomerInformation();
        $cs->setEmail(null);
        $controller = $this->createMockController($cs);
        $this->render($controller);
    }

    public function testWithoutNameAndTitle(): void
    {
        $cs = $this->createCustomerInformation();
        $cs->setName(null);
        $controller = $this->createMockController($cs);
        $this->render($controller, '');
    }

    public function testWithoutURL(): void
    {
        $cs = $this->createCustomerInformation();
        $cs->setUrl(null);
        $controller = $this->createMockController($cs);
        $this->render($controller);
    }

    public function testWithPrintAddress(): void
    {
        $cs = $this->createCustomerInformation();
        $controller = $this->createMockController($cs);
        $this->render($controller);
    }

    private function createCustomerInformation(): CustomerInformation
    {
        $cs = new CustomerInformation();
        $cs->setPrintAddress(true)
            ->setAddress('Address')
            ->setEmail('email@example.com')
            ->setName('Name')
            ->setPhone('Phone')
            ->setUrl('https://www.example.com')
            ->setZipCity('ZipCity');

        return $cs;
    }

    private function createMockController(CustomerInformation $cs): MockObject&AbstractController
    {
        $application = $this->createMock(ApplicationService::class);
        $application->method('getCustomerName')
            ->willReturn('Customer');
        $application->method('getCustomer')
            ->willReturn($cs);

        $service = $this->createMock(UserService::class);
        $service->method('isPrintAddress')
            ->willReturn(true);
        $service->method('getCustomer')
            ->willReturn($cs);

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getUserIdentifier')
            ->willReturn('User');
        $controller->method('getApplicationOwnerUrl')
            ->willReturnCallback(static fn (): string => $cs->getUrl() ?? '');
        $controller->method('getApplicationName')
            ->willReturn('Calculation');
        $controller->method('getApplicationService')
            ->willReturn($application);
        $controller->method('getUserService')
            ->willReturn($service);
        $controller->method('getCustomer')
            ->willReturn($cs);

        return $controller;
    }

    private function render(MockObject&AbstractController $controller, string $title = 'Title'): void
    {
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($controller, $content);
        $doc->setTitle($title);
        $actual = $doc->render();
        self::assertTrue($actual);
    }
}
