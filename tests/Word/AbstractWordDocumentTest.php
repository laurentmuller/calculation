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
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AbstractWordDocumentTest extends TestCase
{
    use TranslatorMockTrait;

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testDefault(): void
    {
        $controller = $this->createMock(AbstractController::class);
        $doc = new class($controller) extends AbstractWordDocument {
            public function render(): bool
            {
                return true;
            }
        };
        $doc->setTitleTrans('id');
        $doc->setPrintAddress(true);

        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithCustomer(): void
    {
        $cs = $this->createCustomerInformation();
        $controller = $this->createMockController($cs);
        $doc = new class($controller) extends AbstractWordDocument {
            public function render(): bool
            {
                return true;
            }
        };
        $doc->setPrintAddress(true);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithEmptyValues(): void
    {
        $cs = new CustomerInformation();
        $controller = $this->createMockController($cs);
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($controller, $content);
        $doc->setPrintAddress(true);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithoutEmail(): void
    {
        $cs = $this->createCustomerInformation();
        $cs->setEmail(null);
        $controller = $this->createMockController($cs);
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($controller, $content);
        $doc->setPrintAddress(true);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithoutURL(): void
    {
        $cs = $this->createCustomerInformation();
        $controller = $this->createMockController($cs);
        $controller->method('getApplicationOwnerUrl')
            ->willReturn('');

        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($controller, $content);
        $doc->setPrintAddress(false);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithPrintAddress(): void
    {
        $cs = $this->createCustomerInformation();
        $controller = $this->createMockController($cs);
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($controller, $content);
        $doc->setPrintAddress(true);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    private function createCustomerInformation(): CustomerInformation
    {
        $cs = new CustomerInformation();
        $cs->setPrintAddress(true)
            ->setAddress('Address')
            ->setEmail('Email')
            ->setFax('Fax')
            ->setName('Name')
            ->setPhone('Phone')
            ->setUrl('URL')
            ->setZipCity('ZipCity');

        return $cs;
    }

    /**
     * @throws Exception
     */
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

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getUserIdentifier')
            ->willReturn('User');
        $controller->method('getApplicationOwnerUrl')
            ->willReturn('https://www.example.com');
        $controller->method('getApplicationName')
            ->willReturn('Calculation');
        $controller->method('getApplicationService')
            ->willReturn($application);
        $controller->method('getUserService')
            ->willReturn($service);

        return $controller;
    }
}
