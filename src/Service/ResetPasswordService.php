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
use App\Mime\NotificationEmail;
use App\Repository\UserRepository;
use App\Traits\LoggerTrait;
use App\Utils\DateUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

/**
 * Service to reset user password.
 */
readonly class ResetPasswordService
{
    use LoggerTrait;

    private const int THROTTLE_MINUTES = 5;
    private const string THROTTLE_OFFSET = 'PT3300S';

    public function __construct(
        private ResetPasswordHelperInterface $helper,
        private UserRepository $repository,
        private UserExceptionService $service,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $generator,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
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
    public function generateFakeResetToken(?int $resetRequestLifetime = null): ResetPasswordToken
    {
        return $this->helper->generateFakeResetToken($resetRequestLifetime);
    }

    /**
     * Gets translated lifetime expires.
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
    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Gets the throttle expire date.
     */
    public function getThrottleAt(ResetPasswordToken $token): DatePoint
    {
        $expireAt = DateUtils::toDatePoint($token->getExpiresAt());

        return DateUtils::sub($expireAt, self::THROTTLE_OFFSET);
    }

    /**
     * Gets the translated throttle lifetime.
     */
    public function getThrottleLifeTime(): string
    {
        return $this->trans('%count% minute|%count% minutes', ['%count%' => self::THROTTLE_MINUTES], 'ResetPasswordBundle');
    }

    /**
     * Handle an exception.
     */
    public function handleException(Request $request, \Throwable $e): void
    {
        $exception = $this->service->handleException($request, $e);
        $message = $this->service->translate($exception);
        $this->logException($exception, $message);
    }

    /**
     * Send the email to the user to resetting the password.
     *
     * @return ResetPasswordToken|false|null false if the user cannot be found; the token on success; null on error
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
            if ('' !== $token->getToken()) {
                $this->helper->removeResetRequest($token->getToken());
            }
            $this->handleException($request, $e);

            return null;
        }
    }

    private function createEmail(User $user, ResetPasswordToken $token): NotificationEmail
    {
        return NotificationEmail::instance($this->translator, 'notification/reset_password.html.twig')
            ->subject(new TranslatableMessage('resetting.request.title'))
            ->importance(Importance::HIGH)
            ->from($this->getAddressFrom())
            ->to($user->getAddress())
            ->action($this->trans('resetting.request.submit'), $this->getResetAction($token))
            ->context([
                'token' => $token->getToken(),
                'username' => $user->getUserIdentifier(),
                'expires_date' => $this->getExpiresAt($token),
                'expires_life_time' => $this->getExpiresLifeTime($token),
                'throttle_date' => $this->getThrottleAt($token),
                'throttle_life_time' => $this->getThrottleLifeTime(),
            ]);
    }

    private function getAddressFrom(): Address
    {
        return ApplicationService::getOwnerAddress();
    }

    private function getExpiresAt(ResetPasswordToken $token): DatePoint
    {
        return DatePoint::createFromInterface($token->getExpiresAt());
    }

    private function getResetAction(ResetPasswordToken $token): string
    {
        return $this->generator->generate(
            ResetPasswordController::ROUTE_RESET,
            ['token' => $token->getToken()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function trans(string $id, array $parameters = [], ?string $domain = null): string
    {
        return $this->translator->trans($id, $parameters, $domain);
    }
}
