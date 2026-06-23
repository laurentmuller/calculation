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

use App\Interfaces\DocumentHelperInterface;
use App\Model\CustomerInformation;
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
        $helper = self::createStub(DocumentHelperInterface::class);
        $doc = new class($helper) extends AbstractWordDocument {
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
        $helper = $this->createMockHelper();
        $doc = new class($helper) extends AbstractWordDocument {
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
        $helper = $this->createMockHelper();
        $doc = new class($helper) extends AbstractWordDocument {
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
        $helper = $this->createMockHelper();
        $this->render($helper);
    }

    public function testWithoutEmail(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setEmail(null);
        $helper = $this->createMockHelper($customerInformation);
        $this->render($helper);
    }

    public function testWithoutNameAndTitle(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setName(null);
        $helper = $this->createMockHelper($customerInformation);
        $this->render($helper, '');
    }

    public function testWithoutURL(): void
    {
        $customerInformation = $this->createCustomerInformation();
        $customerInformation->setUrl(null);
        $helper = $this->createMockHelper($customerInformation);
        $this->render($helper);
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

    private function createMockHelper(?CustomerInformation $customerInformation = null): DocumentHelperInterface
    {
        $customerInformation ??= $this->createCustomerInformation();
        $helper = $this->createMock(DocumentHelperInterface::class);
        $helper->method('getUserIdentifier')
            ->willReturn('User');
        $helper->method('getCustomer')
            ->willReturn($customerInformation);

        return $helper;
    }

    private function render(DocumentHelperInterface $helper, string $title = 'Title'): void
    {
        $content = <<<XML
            <i>Test</i>
            <div>Text</div>
            XML;
        $doc = new HtmlDocument($helper, $content);
        $doc->setTitle($title);
        $actual = $doc->render();
        self::assertTrue($actual);
    }
}
