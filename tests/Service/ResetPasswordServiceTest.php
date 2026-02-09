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
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
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
        $service = $this->createService($helper);
        $service->flush();
        self::expectNotToPerformAssertions();
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
        $service = $this->createService($helper);
        $actual = $service->getLogger();
        self::assertInstanceOf(LoggerInterface::class, $actual);
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
        $service = $this->createService($helper);
        $service->handleException(new Request(), new ExpiredSignatureException());
        self::expectNotToPerformAssertions();
    }

    public function testSendEmailWithMailerException(): void
    {
        $mailer = $this->createMock(MailerInterface::class);
        $mailer->method('send')
            ->willThrowException(new UnexpectedResponseException());
        $helper = $this->createResetPasswordHelper();
        $user = $this->createUser();
        $service = $this->createService($helper, $user, $mailer);
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
        $service = $this->createService($helper, $user);
        $request = new Request();
        $actual = $service->sendEmail($request, $user);
        self::assertNull($actual);
    }

    public function testSendEmailWithUserNameFound(): void
    {
        $helper = $this->createResetPasswordHelper();
        $user = $this->createUser();
        $service = $this->createService($helper, $user);
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

    private function createResetPasswordHelper(): ResetPasswordHelper
    {
        $resetRequestLifetime = 3600;
        $requestThrottleTime = 3600;

        return new ResetPasswordHelper(
            self::createStub(ResetPasswordTokenGenerator::class),
            self::createStub(ResetPasswordCleaner::class),
            self::createStub(ResetPasswordRequestRepositoryInterface::class),
            $resetRequestLifetime,
            $requestThrottleTime
        );
    }

    private function createResetPasswordToken(?\DateTime $date = null): ResetPasswordToken
    {
        $date ??= new DatePoint();

        return new ResetPasswordToken('token', $date, $date->getTimestamp());
    }

    private function createService(
        ResetPasswordHelperInterface $helper,
        ?User $user = null,
        ?MailerInterface $mailer = null
    ): ResetPasswordService {
        $repository = $this->createMock(UserRepository::class);
        if ($user instanceof User) {
            $repository->method('findByUsernameOrEmail')
                ->willReturn($user);
        }
        $translator = $this->createMockTranslator();
        $service = new UserExceptionService($translator);
        $mailer ??= self::createStub(MailerInterface::class);

        return new ResetPasswordService(
            $helper,
            $repository,
            $service,
            $translator,
            self::createStub(UrlGeneratorInterface::class),
            $mailer,
            self::createStub(LoggerInterface::class),
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
