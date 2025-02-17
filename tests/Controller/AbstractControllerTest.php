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

namespace App\Tests\Controller;

use App\Controller\AbstractController;
use App\Interfaces\PropertyServiceInterface;
use App\Report\AbstractReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Response\WordResponse;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Service\UserService;
use App\Spreadsheet\AbstractDocument;
use App\Spreadsheet\SpreadsheetDocument;
use App\Word\AbstractWordDocument;
use App\Word\WordDocument;
use Faker\Container\ContainerException;
use fpdf\PdfDocument;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;

class AbstractControllerTest extends KernelTestCase
{
    public function testGetAddressFrom(): void
    {
        $controller = $this->createController();
        $actual = $controller->getAddressFrom();
        self::assertInstanceOf(Address::class, $actual);
    }

    public function testGetApplicationName(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationName();
        self::assertNotEmpty($actual);
    }

    public function testGetApplicationOwnerUrl(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationOwnerUrl();
        self::assertNotEmpty($actual);
    }

    public function testGetApplicationService(): void
    {
        $controller = $this->createController();
        $actual = $controller->getApplicationService();
        self::assertInstanceOf(ApplicationService::class, $actual);
    }

    public function testGetMinMargin(): void
    {
        $controller = $this->createController();
        $actual = $controller->getMinMargin();
        self::assertSame(PropertyServiceInterface::class::DEFAULT_MIN_MARGIN, $actual);
    }

    public function testGetRequestStack(): void
    {
        $controller = $this->createController();
        $actual = $controller->getRequestStack();
        self::assertInstanceOf(RequestStack::class, $actual);
        // second time for test caching
        $actual = $controller->getRequestStack();
        self::assertInstanceOf(RequestStack::class, $actual);
    }

    public function testGetRequestStackException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getRequestStack();
    }

    public function testGetRequestStackWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getRequestStack();
    }

    public function testGetSubscribedServices(): void
    {
        $controller = $this->createController();
        $actual = $controller::getSubscribedServices();
        self::assertContains(UserService::class, $actual);
        self::assertContains(TranslatorInterface::class, $actual);
        self::assertContains(UrlGeneratorService::class, $actual);
    }

    public function testGetTranslator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getTranslator();
        self::assertInstanceOf(TranslatorInterface::class, $actual);
        // second time for test caching
        $actual = $controller->getTranslator();
        self::assertInstanceOf(TranslatorInterface::class, $actual);
    }

    public function testGetTranslatorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getTranslator();
    }

    public function testGetTranslatorWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getTranslator();
    }

    public function testGetUrlGenerator(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUrlGenerator();
        self::assertInstanceOf(UrlGeneratorService::class, $actual);
        // second time for test caching
        $actual = $controller->getUrlGenerator();
        self::assertInstanceOf(UrlGeneratorService::class, $actual);
    }

    public function testGetUrlGeneratorException(): void
    {
        $controller = $this->createMockController();
        self::expectException(\LogicException::class);
        $controller->getUrlGenerator();
    }

    public function testGetUrlGeneratorWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getUrlGenerator();
    }

    public function testGetUserIdentifier(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserIdentifier();
        self::assertNull($actual);
    }

    public function testGetUserService(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserService();
        self::assertInstanceOf(UserService::class, $actual);
        // second time for test caching
        $actual = $controller->getUserService();
        self::assertInstanceOf(UserService::class, $actual);
    }

    public function testGetUserServiceWithException(): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $controller->getUserService();
    }

    public function testPdfDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $report = new class($controller) extends AbstractReport {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $controller->renderPdfDocument($report);
    }

    public function testPdfDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $report = new class($controller) extends AbstractReport {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $response = $controller->renderPdfDocument($report);
        self::assertInstanceOf(PdfResponse::class, $response);
    }

    public function testRedirectToHomePage(): void
    {
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage();
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function testRedirectToHomePageWithMessage(): void
    {
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage('id', ['key' => 'value']);
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function testRedirectToHomePageWithRequest(): void
    {
        $request = new Request();
        $controller = $this->createController();
        $actual = $controller->redirectToHomePage(request: $request);
        self::assertInstanceOf(RedirectResponse::class, $actual);
    }

    public function testSpreadsheetDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractDocument {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $controller->renderSpreadsheetDocument($doc);
    }

    public function testSpreadsheetDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $response = $controller->renderSpreadsheetDocument($doc);
        self::assertInstanceOf(SpreadsheetResponse::class, $response);
    }

    /**
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function testWordDocumentWithException(): void
    {
        self::expectException(NotFoundHttpException::class);
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return false;
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $controller->renderWordDocument($doc);
    }

    /**
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    public function testWordDocumentWithTitle(): void
    {
        $controller = $this->createController();
        $doc = new class($controller) extends AbstractWordDocument {
            #[\Override]
            public function render(): bool
            {
                return true;
            }

            #[\Override]
            public function getTitle(): string
            {
                return 'Fake';
            }
        };
        /**
         * @psalm-suppress InaccessibleMethod
         *
         * @phpstan-ignore method.protected
         */
        $response = $controller->renderWordDocument($doc);
        self::assertInstanceOf(WordResponse::class, $response);
    }

    private function createController(): AbstractController
    {
        return new class(self::getContainer()) extends AbstractController {
            public function __construct(ContainerInterface $container)
            {
                $this->setContainer($container);
            }

            #[\Override]
            public function renderPdfDocument(
                PdfDocument $doc,
                bool $inline = true,
                string $name = ''
            ): PdfResponse {
                return parent::renderPdfDocument($doc, $inline, $name);
            }

            #[\Override]
            public function renderSpreadsheetDocument(
                SpreadsheetDocument $doc,
                bool $inline = true,
                string $name = ''
            ): SpreadsheetResponse {
                return parent::renderSpreadsheetDocument($doc, $inline, $name);
            }

            #[\Override]
            public function renderWordDocument(
                WordDocument $doc,
                bool $inline = true,
                string $name = ''
            ): WordResponse {
                return parent::renderWordDocument($doc, $inline, $name);
            }
        };
    }

    private function createMockController(): AbstractController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willThrowException(new ContainerException());

        return new class($container) extends AbstractController {
            public function __construct(ContainerInterface $container)
            {
                $this->setContainer($container);
            }
        };
    }
}
