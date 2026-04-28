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

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\ResetPasswordService;
use App\Service\UserExceptionService;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\UnexpectedResponseException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\InvalidResetPasswordTokenException;
use SymfonyCasts\Bundle\ResetPassword\Generator\ResetPasswordTokenGenerator;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelper;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use SymfonyCasts\Bundle\ResetPassword\Util\ResetPasswordCleaner;
use SymfonyCasts\Bundle\VerifyEmail\Exception\ExpiredSignatureException;

final class ResetPasswordServiceTest extends TestCase
{
    use IdTrait;
    use TranslatorMockTrait;

    public function testFlush(): void
    {
        $helper = $this->createResetPasswordHelper();
        $repository = $this->createRepository();
        $repository->expects(self::once())
            ->method('flush');
        $service = $this->createService(
            helper: $helper,
            repository: $repository,
        );
        $service->flush();
    }

    public function testGenerateFakeResetToken(): void
    {
        $helper = $this->createResetPasswordHelper();
        $service = $this->createService($helper);
        $token = $service->generateFakeResetToken();
        $actual = \strlen($token->getToken());
        self::assertSame(32, $actual);
    }

    public function testGetExpiresLifeTime(): void
    {
        $helper = $this->createResetPasswordHelper();
        $service = $this->createService($helper);
        $token = $this->createResetPasswordToken();
        $actual = $service->getExpiresLifeTime($token);
        self::assertSame('%count% minute|%count% minutes', $actual);
    }

    public function testGetLogger(): void
    {
        $helper = $this->createResetPasswordHelper();
        $service = $this->createService(
            helper: $helper,
            logger: new NullLogger(),
        );
        $actual = $service->getLogger();
        self::assertInstanceOf(NullLogger::class, $actual);
    }

    public function testGetThrottleLifeTime(): void
    {
        $helper = $this->createResetPasswordHelper();
        $service = $this->createService($helper);
        $actual = $service->getThrottleLifeTime();
        self::assertSame('%count% minute|%count% minutes', $actual);
    }

    public function testHandleException(): void
    {
        $helper = $this->createResetPasswordHelper();
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('error');
        $service = $this->createService(
            helper: $helper,
            logger: $logger,
        );
        $service->handleException(new Request(), new ExpiredSignatureException());
    }

    public function testSendEmailWithMailerException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException(new UnexpectedResponseException());
        $helper = $this->createResetPasswordHelper();
        $user = $this->createUser();
        $service = $this->createService(
            helper: $helper,
            user: $user,
            mailer: $mailer
        );
        $request = new Request();
        $actual = $service->sendEmail($request, $user);
        self::assertNull($actual);
    }

    public function testSendEmailWithTokenException(): void
    {
        $helper = $this->createMock(ResetPasswordHelperInterface::class);
        $helper->method('generateResetToken')
            ->willThrowException(new InvalidResetPasswordTokenException());
        $user = $this->createUser();
        $service = $this->createService(
            helper: $helper,
            user: $user
        );
        $request = new Request();
        $actual = $service->sendEmail($request, $user);
        self::assertNull($actual);
    }

    public function testSendEmailWithUserNameFound(): void
    {
        $helper = $this->createResetPasswordHelper();
        $user = $this->createUser();
        $service = $this->createService(
            helper: $helper,
            user: $user
        );
        $request = new Request();
        $actual = $service->sendEmail($request, $user->getUsername());
        self::assertInstanceOf(ResetPasswordToken::class, $actual);
    }

    public function testSendEmailWithUserNameNotFound(): void
    {
        $helper = $this->createResetPasswordHelper();
        $service = $this->createService($helper);
        $actual = $service->sendEmail(new Request(), 'fake');
        self::assertFalse($actual);
    }

    private function createRepository(?User $user = null): MockObject&UserRepository
    {
        $repository = $this->createMock(UserRepository::class);
        $repository->method('findByUsernameOrEmail')
            ->willReturn($user);

        return $repository;
    }

    private function createResetPasswordHelper(): ResetPasswordHelper
    {
        return new ResetPasswordHelper(
            generator: self::createStub(ResetPasswordTokenGenerator::class),
            cleaner: self::createStub(ResetPasswordCleaner::class),
            repository: self::createStub(ResetPasswordRequestRepositoryInterface::class),
            resetRequestLifetime: 3600,
            requestThrottleTime: 3600
        );
    }

    private function createResetPasswordToken(?\DateTimeInterface $date = null): ResetPasswordToken
    {
        $date ??= new DatePoint();

        return new ResetPasswordToken('token', $date, $date->getTimestamp());
    }

    private function createService(
        ResetPasswordHelperInterface $helper,
        ?UserRepository $repository = null,
        ?User $user = null,
        ?MailerInterface $mailer = null,
        ?LoggerInterface $logger = null,
    ): ResetPasswordService {
        $repository ??= $this->createRepository($user);
        $translator = $this->createMockTranslator();
        $service = new UserExceptionService($translator);
        $mailer ??= self::createStub(MailerInterface::class);
        $logger ??= self::createStub(LoggerInterface::class);

        return new ResetPasswordService(
            $helper,
            $repository,
            $service,
            $translator,
            self::createStub(UrlGeneratorInterface::class),
            $mailer,
            $logger,
        );
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('username')
            ->setEmail('email@example.com');

        return self::setId($user);
    }
}
