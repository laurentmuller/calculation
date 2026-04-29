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
use App\Tests\TranslatorMockTrait;
use App\Word\AbstractWordDocument;
use App\Word\HtmlDocument;
use App\Word\WordFooter;
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

    public function testFooterWithoutUrl(): void
    {
        $controller = $this->createMockController();
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                $section = $this->addSection();
                $footer = new WordFooter($this);
                $footer->setName('name')
                    ->output($section);

                return true;
            }
        };
        $actual = $doc->render();
        self::assertTrue($actual);
    }

    public function testWithCustomer(): void
    {
        $controller = $this->createMockController();
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
        $controller = $this->createMockController();
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
        $controller = $this->createMockController();
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

    private function createMockController(?CustomerInformation $customerInformation = null): AbstractController
    {
        $customerInformation ??= $this->createCustomerInformation();

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

        $controller = $this->createMock(AbstractController::class);
        $controller->method('getUserIdentifier')
            ->willReturn('User');
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
