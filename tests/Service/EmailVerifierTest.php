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
use App\Mime\NotificationEmail;
use App\Repository\UserRepository;
use App\Service\EmailVerifier;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Generator\VerifyEmailTokenGenerator;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\Util\VerifyEmailQueryUtility;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

final class EmailVerifierTest extends TestCase
{
    use IdTrait;
    use TranslatorMockTrait;

    public function testHandleEmail(): void
    {
        $service = new EmailVerifier(
            $this->createVerifyEmailHelper(),
            self::createStub(MailerInterface::class),
            self::createStub(UserRepository::class),
            $this->createMockTranslator()
        );

        $user = $this->createUser();
        $request = new Request(['expires' => \time() + 3600]);
        $service->handleEmail($request, $user);
        self::assertTrue($user->isVerified());
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function testSendEmail(): void
    {
        $translator = $this->createMockTranslator();
        $service = new EmailVerifier(
            $this->createMockVerifyEmailHelper(),
            self::createStub(MailerInterface::class),
            self::createStub(UserRepository::class),
            $translator
        );

        $user = $this->createUser();
        $email = NotificationEmail::instance($translator);
        $service->sendEmail('route', $user, $email);
        self::assertFalse($user->isVerified());
    }

    private function createMockVerifyEmailHelper(): MockObject&VerifyEmailHelperInterface
    {
        $date = new DatePoint();
        $component = new VerifyEmailSignatureComponents($date, 'uri', $date->getTimestamp());
        $helper = $this->createMock(VerifyEmailHelperInterface::class);
        $helper->method('generateSignature')
            ->willReturn($component);

        return $helper;
    }

    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('username')
            ->setEmail('email@example.com');

        return self::setId($user);
    }

    private function createVerifyEmailHelper(): VerifyEmailHelper
    {
        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner->method('checkRequest')
            ->willReturn(true);

        return new VerifyEmailHelper(
            self::createStub(UrlGeneratorInterface::class),
            $uriSigner,
            self::createStub(VerifyEmailQueryUtility::class),
            self::createStub(VerifyEmailTokenGenerator::class),
            3600
        );
    }
}
