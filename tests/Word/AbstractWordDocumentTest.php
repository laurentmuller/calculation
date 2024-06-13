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
use App\Word\AbstractHeaderFooter;
use App\Word\AbstractWordDocument;
use App\Word\HtmlDocument;
use App\Word\WordFooter;
use App\Word\WordHeader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[CoversClass(AbstractWordDocument::class)]
#[CoversClass(AbstractHeaderFooter::class)]
#[CoversClass(WordHeader::class)]
#[CoversClass(WordFooter::class)]
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
        $doc->setPrintAddress(false);
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception|\PhpOffice\PhpWord\Exception\Exception
     */
    public function testWithoutURL(): void
    {
        $cs = $this->createCustomerInformation();
        $cs->setUrl(null);
        $controller = $this->createMockController($cs);

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
        $application->expects(self::any())
            ->method('getCustomerName')
            ->willReturn('Customer');
        $application->expects(self::any())
            ->method('getCustomer')
            ->willReturn($cs);

        $service = $this->createMock(UserService::class);
        $service->expects(self::any())
            ->method('isPrintAddress')
            ->willReturn(true);

        $controller = $this->createMock(AbstractController::class);
        $controller->expects(self::any())
            ->method('getUserIdentifier')
            ->willReturn('User');
        $controller->expects(self::any())
            ->method('getApplicationOwnerUrl')
            ->willReturn('https://www.example.com');
        $controller->expects(self::any())
            ->method('getApplicationName')
            ->willReturn('Calculation');
        $controller->expects(self::any())
            ->method('getApplicationService')
            ->willReturn($application);
        $controller->expects(self::any())
            ->method('getUserService')
            ->willReturn($service);

        return $controller;
    }
}
