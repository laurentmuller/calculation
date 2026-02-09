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
use App\Parameter\ApplicationParameters;
use App\Parameter\OptionsParameter;
use App\Parameter\UserParameters;
use App\Service\ApplicationService;
use App\Tests\TranslatorMockTrait;
use App\Word\AbstractWordDocument;
use App\Word\HtmlDocument;
use PHPUnit\Framework\TestCase;

final class AbstractWordDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    public function testDefault(): void
    {
        $controller = self::createStub(AbstractController::class);
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
        $customerInformation = $this->createCustomerInformation();
        $controller = $this->createMockController($customerInformation);
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
        $customerInformation = new CustomerInformation();
        $controller = $this->createMockController($customerInformation);
        $this->render($controller);
    }

    public function testWithoutEmail(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setEmail(null);
        $controller = $this->createMockController($customerInformation);
        $this->render($controller);
    }

    public function testWithoutNameAndTitle(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setName(null);
        $controller = $this->createMockController($customerInformation);
        $this->render($controller, '');
    }

    public function testWithoutURL(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setUrl(null);
        $controller = $this->createMockController($customerInformation);
        $this->render($controller);
    }

    public function testWithPrintAddress(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $controller = $this->createMockController($customerInformation);
        $this->render($controller);
    }

    private function createCustomerInformation(): CustomerInformation
    {
        $customerInformation = new CustomerInformation();
        $customerInformation->setPrintAddress(true)
            ->setAddress('Address')
            ->setEmail('email@example.com')
            ->setName('Name')
            ->setPhone('Phone')
            ->setUrl('https://www.example.com')
            ->setZipCity('ZipCity');

        return $customerInformation;
    }

    private function createMockController(CustomerInformation $customerInformation): AbstractController
    {
        $applicationParameters = $this->createMock(ApplicationParameters::class);
        $applicationParameters->method('getCustomerInformation')
            ->willReturn($customerInformation);

        $options = $this->createMock(OptionsParameter::class);
        $options->method('isPrintAddress')
            ->willReturn(true);
        $userParameters = $this->createMock(UserParameters::class);
        $userParameters->method('getOptions')
            ->willReturn($options);
        $userParameters->method('getCustomerInformation')
            ->willReturn($customerInformation);

        $applicationService = $this->createMock(ApplicationService::class);
        $applicationService->method('getName')
            ->willReturn('Calculation');
        $applicationService->method('getFullName')
            ->willReturn('Calculation');

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getUserIdentifier')
            ->willReturn('User');
        $controller->method('getApplicationService')
            ->willReturn($applicationService);
        $controller->method('getApplicationParameters')
            ->willReturn($applicationParameters);
        $controller->method('getUserParameters')
            ->willReturn($userParameters);
        $controller->method('getCustomer')
            ->willReturn($customerInformation);

        return $controller;
    }

    private function render(AbstractController $controller, string $title = 'Title'): void
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
