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

namespace App\Service;

use App\Controller\ResetPasswordController;
use App\Entity\User;
use App\Enums\Importance;
use App\Mime\ResetPasswordEmail;
use App\Repository\UserRepository;
use App\Traits\LoggerTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Service to reset user password.
 */
class ResetPasswordService
{
    use LoggerTrait;

    private const THROTTLE_MINUTES = 5;
    private const THROTTLE_OFFSET = 'PT3300S';

    public function __construct(
        private readonly ResetPasswordHelperInterface $helper,
        private readonly UserRepository $repository,
        private readonly UserExceptionService $service,
        private readonly TranslatorInterface $translator,
        private readonly UrlGeneratorInterface $generator,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        #[Autowire('%mailer_user_email%')]
        private readonly string $mailerUserEmail,
        #[Autowire('%mailer_user_name%')]
        private readonly string $mailerUserName
    ) {
    }

    /**
     * Flushes all changes to objects that have been queued to the database.
     */
    public function flush(): void
    {
        $this->repository->flush();
    }

    /**
     * Generates a fake reset password token.
     */
    public function generateFakeResetToken(int $resetRequestLifetime = null): ResetPasswordToken
    {
        return $this->helper->generateFakeResetToken($resetRequestLifetime);
    }

    /**
     * Gets the translated expires lifetime.
     */
    public function getExpiresLifeTime(ResetPasswordToken $token): string
    {
        return $this->trans(
            $token->getExpirationMessageKey(),
            $token->getExpirationMessageData(),
            'ResetPasswordBundle'
        );
    }

    /**
     * Gets logger.
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the throttle expire date.
     */
    public function getThrottleAt(ResetPasswordToken $token): \DateTimeInterface
    {
        /** @psalm-var \DateTime $expireAt */
        $expireAt = clone $token->getExpiresAt();
        $interval = new \DateInterval(self::THROTTLE_OFFSET);

        return $expireAt->sub($interval);
    }

    /**
     * Gets the translated throttle lifetime.
     */
    public function getThrottleLifeTime(): string
    {
        return $this->trans('%count% minute|%count% minutes', ['%count%' => self::THROTTLE_MINUTES], 'ResetPasswordBundle');
    }

    /**
     * Handle an exception by set the authentication error to the session, if applicable;  and log it.
     */
    public function handleException(Request $request, \Throwable $e): void
    {
        $this->logException($this->service->handleException($request, $e));
    }

    /**
     * Send email to the user for resetting the password.
     *
     * @return ResetPasswordToken|false|null false if the user can not be found; the token on success; null on error
     */
    public function sendEmail(Request $request, User|string $user): ResetPasswordToken|false|null
    {
        if (\is_string($user)) {
            $user = $this->repository->findByUsernameOrEmail($user);
        }
        if (!$user instanceof User) {
            return false;
        }

        try {
            $token = $this->helper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->handleException($request, $e);

            return null;
        }

        try {
            $notification = $this->createEmail($user, $token);
            $this->mailer->send($notification);

            return $token;
        } catch (TransportExceptionInterface $e) {
            $this->helper->removeResetRequest($token->getToken());
            $this->handleException($request, $e);

            return null;
        }
    }

    private function createEmail(User $user, ResetPasswordToken $token): ResetPasswordEmail
    {
        $parameters = [
            'token' => $token->getToken(),
            'username' => $user->getUserIdentifier(),
            'expires_date' => $token->getExpiresAt(),
            'expires_life_time' => $this->getExpiresLifeTime($token),
            'throttle_date' => $this->getThrottleAt($token),
            'throttle_life_time' => $this->getThrottleLifeTime(),
        ];
        $email = new ResetPasswordEmail();

        return $email
            ->to($user->getEmailAddress())
            ->from($this->getAddressFrom())
            ->subject($this->trans('resetting.request.title'))
            ->update(Importance::HIGH, $this->translator)
            ->action($this->trans('resetting.request.submit'), $this->getResetAction($token))
            ->context(\array_merge($email->getContext(), $parameters));
    }

    private function getAddressFrom(): Address
    {
        return new Address($this->mailerUserEmail, $this->mailerUserName);
    }

    private function getResetAction(ResetPasswordToken $token): string
    {
        return $this->generator->generate(
            ResetPasswordController::ROUTE_RESET,
            ['token' => $token->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function trans(string $id, array $parameters = [], string $domain = null): string
    {
        return $this->translator->trans($id, $parameters, $domain);
    }
}
