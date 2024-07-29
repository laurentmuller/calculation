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
use App\Mime\RegistrationEmail;
use App\Repository\UserRepository;
use App\Service\EmailVerifier;
use App\Tests\Entity\IdTrait;
use App\Tests\TranslatorMockTrait;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use SymfonyCasts\Bundle\VerifyEmail\Generator\VerifyEmailTokenGenerator;
use SymfonyCasts\Bundle\VerifyEmail\Model\VerifyEmailSignatureComponents;
use SymfonyCasts\Bundle\VerifyEmail\Util\VerifyEmailQueryUtility;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelper;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;

class EmailVerifierTest extends TestCase
{
    use IdTrait;
    use TranslatorMockTrait;

    /**
     * @throws Exception
     * @throws VerifyEmailExceptionInterface
     * @throws \ReflectionException
     */
    public function testHandleEmail(): void
    {
        $helper = $this->createVerifyEmailHelper();
        $mailer = $this->createMock(MailerInterface::class);
        $repository = $this->createMock(UserRepository::class);
        $translator = $this->createMockTranslator();
        $service = new EmailVerifier($helper, $mailer, $repository, $translator);

        $user = $this->createUser();
        $request = new Request(['expires' => \time() + 3600]);
        $service->handleEmail($request, $user);
        self::assertTrue($user->isVerified());
    }

    /**
     * @throws Exception
     * @throws TransportExceptionInterface
     * @throws \ReflectionException
     */
    public function testSendEmail(): void
    {
        $helper = $this->createMockVerifyEmailHelper();
        $mailer = $this->createMock(MailerInterface::class);
        $repository = $this->createMock(UserRepository::class);
        $translator = $this->createMockTranslator();
        $service = new EmailVerifier($helper, $mailer, $repository, $translator);

        $user = $this->createUser();
        $email = RegistrationEmail::create();
        $service->sendEmail('route', $user, $email);
        self::assertFalse($user->isVerified());
    }

    /**
     * @throws Exception
     */
    private function createMockVerifyEmailHelper(): MockObject&VerifyEmailHelperInterface
    {
        $date = new \DateTime();
        $component = new VerifyEmailSignatureComponents($date, 'uri', $date->getTimestamp());
        $helper = $this->createMock(VerifyEmailHelperInterface::class);
        $helper->method('generateSignature')
            ->willReturn($component);

        return $helper;
    }

    /**
     * @throws \ReflectionException
     */
    private function createUser(): User
    {
        $user = new User();
        $user->setUsername('username')
            ->setEmail('email@example.com');

        return self::setId($user);
    }

    /**
     * @throws Exception
     */
    private function createVerifyEmailHelper(): VerifyEmailHelper
    {
        $router = $this->createMock(UrlGeneratorInterface::class);
        $uriSigner = $this->createMock(UriSigner::class);
        $uriSigner->method('checkRequest')
            ->willReturn(true);

        $queryUtility = $this->createMock(VerifyEmailQueryUtility::class);
        $generator = $this->createMock(VerifyEmailTokenGenerator::class);

        return new VerifyEmailHelper(
            $router,
            $uriSigner,
            $queryUtility,
            $generator,
            3600
        );
    }
}
