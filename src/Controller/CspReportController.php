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

use App\Attribute\ForUser;
use App\Attribute\GetRoute;
use App\Enums\Importance;
use App\Mime\NotificationEmail;
use App\Service\ApplicationService;
use App\Utils\StringUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatableMessage;

/**
 * Controller to send CSP violations by e-mail.
 */
#[ForUser]
class CspReportController extends AbstractController
{
    /** The route name. */
    public const string ROUTE_NAME = 'log_csp';

    #[GetRoute(path: '/csp', name: self::ROUTE_NAME)]
    public function invoke(Request $request, LoggerInterface $logger, MailerInterface $mailer): Response
    {
        $content = StringUtils::trim($request->getContent());
        if (null === $content) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        try {
            $context = $this->getContext($content);
            $message = $this->trans('notification.csp_title');
            $logger->error($message, $context);
            $this->sendNotification($context, $mailer);
        } catch (\InvalidArgumentException|TransportExceptionInterface $e) {
            $message = $this->trans('notification.csp_error');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);
        } finally {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }
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
        return $this->generateUrl(route: self::HOME_PAGE, referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @throws \InvalidArgumentException
     */
    private function getContext(string $content): array
    {
        /** @phpstan-var array{csp-report?: string[]} $data */
        $data = StringUtils::decodeJson($content);
        if (!isset($data['csp-report'])) {
            throw new \InvalidArgumentException('Content without "csp-report".');
        }
        $context = \array_filter($data['csp-report']);
        if (isset($context['original-policy'])) {
            $context['original-policy'] = $this->explodeOriginalPolicy($context['original-policy']);
        }

        return $context;
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendNotification(array $context, MailerInterface $mailer): void
    {
        $address = ApplicationService::getOwnerAddress();
        $notification = NotificationEmail::instance($this->getTranslator(), 'notification/csp_violation.html.twig')
            ->subject(new TranslatableMessage('notification.csp_title'))
            ->from($address)
            ->to($address)
            ->context(['context' => $context])
            ->action($this->trans('index.title'), $this->getActionUrl())
            ->importance(Importance::HIGH);

        $mailer->send($notification);
    }
}
