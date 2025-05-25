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

namespace App\Controller;

use App\Attribute\GetRoute;
use App\Enums\Importance;
use App\Interfaces\RoleInterface;
use App\Mime\CspViolationEmail;
use App\Utils\StringUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to send CSP violations by e-mail.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_USER)]
class CspReportController extends AbstractController
{
    /**
     * The route name.
     */
    public const ROUTE_NAME = 'log_csp';

    #[GetRoute(path: '/csp', name: self::ROUTE_NAME)]
    public function invoke(LoggerInterface $logger, MailerInterface $mailer): Response
    {
        $context = $this->getContext();
        if (false === $context) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        try {
            $message = $this->trans('notification.csp_title');
            $logger->error($message, $context);
            $this->sendNotification($message, $context, $mailer);
        } catch (TransportExceptionInterface $e) {
            $message = $this->trans('notification.csp_error');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);
        }

        return new Response(status: Response::HTTP_NO_CONTENT);
    }

    private function explodeOriginalPolicy(string $value): array
    {
        $result = [];
        $policies = \array_filter(\explode(';', $value));
        foreach ($policies as $policy) {
            $entries = \array_filter(\explode(' ', $policy));
            if (\count($entries) > 1) {
                $key = \reset($entries);
                $values = \array_map(
                    static fn (string $entry): string => \trim($entry, "'"),
                    \array_slice($entries, 1)
                );
                \sort($values);
                $result[$key] = \implode(StringUtils::NEW_LINE, $values);
            }
        }

        return $result;
    }

    private function getActionUrl(): string
    {
        return $this->generateUrl(self::HOME_PAGE, [], UrlGeneratorInterface::ABSOLUTE_URL);
    }

    private function getContext(): array|false
    {
        try {
            $content = \file_get_contents('php://input');
            if (false === $content || '' === $content) {
                return false;
            }

            /** @phpstan-var array{csp-report: string[]} $data */
            $data = StringUtils::decodeJson($content);
            $context = \array_filter($data['csp-report']);
            if (isset($context['original-policy'])) {
                $context['original-policy'] = $this->explodeOriginalPolicy($context['original-policy']);
            }

            return $context;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendNotification(string $subject, array $context, MailerInterface $mailer): void
    {
        $notification = CspViolationEmail::create()
            ->subject($subject)
            ->to($this->getAddressFrom())
            ->from($this->getAddressFrom())
            ->context(['context' => $context])
            ->action($this->trans('index.title'), $this->getActionUrl())
            ->updateImportance(Importance::HIGH, $this->getTranslator());

        $mailer->send($notification);
    }
}
