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

use App\Entity\User;
use App\Enums\EntityPermission;
use App\Form\FormHelper;
use App\Model\TranslatableFlashMessage;
use App\Parameter\ApplicationParameters;
use App\Parameter\UserParameters;
use App\Report\AbstractReport;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Spreadsheet\AbstractDocument;
use App\Tests\DatabaseTrait;
use App\Tests\Fixture\FixtureController;
use App\Word\AbstractWordDocument;
use Faker\Container\ContainerException;
use Psr\Container\ContainerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\FrameworkBundle\Test\TestBrowserToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

final class AbstractControllerTest extends KernelTestCase
{
    use DatabaseTrait;

    public function testApplicationParameters(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): ApplicationParameters => $controller->getApplicationParameters(),
            ApplicationParameters::class
        );
    }

    public function testApplicationService(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): ApplicationService => $controller->getApplicationService(),
            ApplicationService::class
        );
    }

    public function testApplicationServiceWithException(): void
    {
        $this->handleServiceWithException(
            static fn (FixtureController $controller): ApplicationService => $controller->getApplicationService()
        );
    }

    public function testCookiePath(): void
    {
        $controller = $this->createController();
        $actual = $controller->getCookiePath();
        self::assertStringStartsWith('/', $actual);
    }

    public function testCreateFormHelper(): void
    {
        $controller = $this->createController();
        $actual = $controller->createFormHelper();
        self::assertSameClass(FormHelper::class, $actual);
    }

    public function testDenyAccessUnlessGranted(): void
    {
        self::expectException(AccessDeniedException::class);
        $controller = $this->createController();
        $controller->denyAccessUnlessGranted(
            attribute: EntityPermission::ADD,
            subject: User::class,
        );
    }

    public function testMinMargin(): void
    {
        $controller = $this->createController();
        $actual = $controller->getMinMargin();
        self::assertSame(1.1, $actual);
    }

    public function testRedirectToHomePage(): void
    {
        $controller = $this->createController();
        $response = $controller->redirectToHomePage();
        self::assertTrue($response->isRedirect());
    }

    public function testRedirectToHomePageWithMessage(): void
    {
        $controller = $this->createController();
        $response = $controller->redirectToHomePage(
            message: 'log.list.empty'
        );
        self::assertTrue($response->isRedirect());
    }

    public function testRedirectToHomePageWithRequest(): void
    {
        $request = new Request();
        $controller = $this->createController();
        $response = $controller->redirectToHomePage(request: $request);
        self::assertTrue($response->isRedirect());
    }

    public function testRedirectToHomePageWithTranslatableMessage(): void
    {
        $controller = $this->createController();
        $response = $controller->redirectToHomePage(
            message: TranslatableFlashMessage::instance(
                message: 'log.list.empty',
                parameters: ['key' => 'value'],
            )
        );
        self::assertTrue($response->isRedirect());
    }

    public function testRelativePathFound(): void
    {
        $expected = 'tests/Controller';
        $controller = $this->createController();
        $actual = $controller->getRelativePath(__DIR__);
        self::assertSame($expected, $actual);
    }

    public function testRelativePathNotFound(): void
    {
        $path = 'fake_dir/fake.txt';
        $controller = $this->createController();
        $actual = $controller->getRelativePath($path);
        self::assertSame($path, $actual);
    }

    public function testRenderPdfDocumentWithException(): void
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
        $controller->renderPdfDocument($report);
    }

    public function testRenderPdfDocumentWithTitle(): void
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
        $response = $controller->renderPdfDocument($report);
        self::assertTrue($response->isOk());
    }

    public function testRenderSpreadsheetDocumentWithException(): void
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
        $controller->renderSpreadsheetDocument($doc);
    }

    public function testRenderSpreadsheetDocumentWithTitle(): void
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
        $response = $controller->renderSpreadsheetDocument($doc);
        self::assertTrue($response->isOk());
    }

    public function testRenderWordDocumentWithException(): void
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
        $controller->renderWordDocument($doc);
    }

    public function testRenderWordDocumentWithTitle(): void
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
        $response = $controller->renderWordDocument($doc);
        self::assertTrue($response->isOk());
    }

    public function testRequestStack(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): RequestStack => $controller->getRequestStack(),
            RequestStack::class
        );
    }

    public function testRequestStackWithException(): void
    {
        $this->handleServiceWithException(
            static fn (FixtureController $controller): RequestStack => $controller->getRequestStack()
        );
    }

    public function testSubscribedServices(): void
    {
        $controller = $this->createController();
        $actual = $controller::getSubscribedServices();
        self::assertContains(ApplicationService::class, $actual);
        self::assertContains(TranslatorInterface::class, $actual);
        self::assertContains(UrlGeneratorService::class, $actual);
        self::assertContains(UserParameters::class, $actual);
    }

    public function testTranslator(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): TranslatorInterface => $controller->getTranslator(),
            TranslatorInterface::class
        );
    }

    public function testTranslatorWithException(): void
    {
        $this->handleServiceWithException(
            static fn (FixtureController $controller): TranslatorInterface => $controller->getTranslator()
        );
    }

    public function testUrlGenerator(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): UrlGeneratorService => $controller->getUrlGenerator(),
            UrlGeneratorService::class
        );
    }

    public function testUrlGeneratorWithException(): void
    {
        $this->handleServiceWithException(
            static fn (FixtureController $controller): UrlGeneratorService => $controller->getUrlGenerator()
        );
    }

    public function testUserIdentifier(): void
    {
        $controller = $this->createController();
        $actual = $controller->getUserIdentifier();
        self::assertNotNull($actual);
    }

    public function testUserParameters(): void
    {
        $this->handleService(
            static fn (FixtureController $controller): UserParameters => $controller->getUserParameters(),
            UserParameters::class
        );
    }

    public function testUserParametersWithException(): void
    {
        $this->handleServiceWithException(
            static fn (FixtureController $controller): UserParameters => $controller->getUserParameters()
        );
    }

    /**
     * @phpstan-param class-string $expected
     */
    protected static function assertSameClass(string $expected, object $actual): void
    {
        self::assertInstanceOf($expected, $actual);
    }

    private function createController(): FixtureController
    {
        $this->login();

        return new FixtureController(self::getContainer());
    }

    private function createMockController(): FixtureController
    {
        $container = $this->createMock(ContainerInterface::class);
        $container->method('get')
            ->willThrowException(new ContainerException());

        return new FixtureController($container);
    }

    /**
     * @template TService
     *
     * @param callable(FixtureController): TService $callback
     * @param class-string<TService>                $expected
     */
    private function handleService(callable $callback, string $expected): void
    {
        $controller = $this->createController();
        $actual = $callback($controller);
        self::assertSameClass($expected, $actual);
    }

    /**
     * @template TService
     *
     * @param callable(FixtureController): TService $callback
     */
    private function handleServiceWithException(callable $callback): void
    {
        self::expectException(\LogicException::class);
        $controller = $this->createMockController();
        $callback($controller);
    }

    private function login(): void
    {
        $user = (new User())->setUsername('test');
        $token = new TestBrowserToken($user->getRoles(), $user);
        /** @phpstan-var TokenStorageInterface $storage */
        $storage = self::getContainer()->get(TokenStorageInterface::class);
        $storage->setToken($token);
    }
}
