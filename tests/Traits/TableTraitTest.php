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

namespace App\Tests\Traits;

use App\Entity\CalculationState;
use App\Enums\FlashType;
use App\Service\UserService;
use App\Table\AbstractTable;
use App\Table\DataQuery;
use App\Tests\TranslatorMockTrait;
use App\Traits\ExceptionContextTrait;
use App\Traits\TableTrait;
use App\Traits\TranslatorTrait;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @psalm-suppress ExtensionRequirementViolation
 *
 * @phpstan-ignore class.missingExtends
 */
class TableTraitTest extends TestCase
{
    use ExceptionContextTrait;
    use TableTrait;
    use TranslatorMockTrait;
    use TranslatorTrait;

    private bool $denyException;
    private bool $throwException;
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        $this->denyException = false;
        $this->throwException = false;
        $this->translator = $this->createMockTranslator();
    }

    public function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null): void
    {
        if ($this->denyException) {
            throw new AccessDeniedException();
        }
    }

    #[\Override]
    public function getCookiePath(): string
    {
        return '/';
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function getUserService(): UserService
    {
        if ($this->throwException) {
            throw new \LogicException();
        }

        return $this->createMock(UserService::class);
    }

    public function json(mixed $data, int $status = 200): JsonResponse
    {
        return new JsonResponse($data, $status);
    }

    public function redirectToHomePage(?string $id = null, FlashType $type = FlashType::SUCCESS): Response
    {
        return new Response(($id ?? '') . $type->getIcon());
    }

    public function render(string $view, array $parameters = []): Response
    {
        return new Response(content: $view, headers: $parameters);
    }

    public function testDenyAccess(): void
    {
        $this->denyException = true;
        $table = $this->createMock(AbstractTable::class);
        $table->expects(self::once())
            ->method('getEntityClassName')
            ->willReturn(CalculationState::class);

        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $template = '';
        self::expectException(AccessDeniedException::class);
        $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );
    }

    public function testEmptyMessage(): void
    {
        $table = $this->createMock(AbstractTable::class);
        $table->expects(self::once())
            ->method('getEmptyMessage')
            ->willReturn('Empty Message');
        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $template = '';
        $actual = $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );
        self::assertNotInstanceOf(JsonResponse::class, $actual);
    }

    public function testThrowException(): void
    {
        $this->throwException = true;
        $table = $this->createMock(AbstractTable::class);
        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $template = '';
        $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );
        self::expectNotToPerformAssertions();
    }

    public function testThrowExceptionJson(): void
    {
        $this->throwException = true;
        $table = $this->createMock(AbstractTable::class);
        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $query->callback = true;
        $template = '';
        $actual = $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );
        self::assertInstanceOf(JsonResponse::class, $actual);
    }

    public function testValidJsonResponse(): void
    {
        $table = $this->createMock(AbstractTable::class);
        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $query->callback = true;
        $template = '';
        $actual = $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );

        self::assertInstanceOf(JsonResponse::class, $actual);
    }

    public function testValidResponse(): void
    {
        $table = $this->createMock(AbstractTable::class);
        $logger = $this->createMock(LoggerInterface::class);
        $query = new DataQuery();
        $template = '';
        $this->handleTableRequest(
            $table,
            $logger,
            $query,
            $template
        );
        self::expectNotToPerformAssertions();
    }
}
