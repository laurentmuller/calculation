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

use App\Attribute\IsUser;
use App\Enums\Importance;
use App\Mime\NotificationEmail;
use App\Service\ApplicationService;
use App\Utils\StringUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to log and send an e-mail for CSP and permission violations.
 */
#[IsUser]
class CspReportController extends AbstractController
{
    /** The route name. */
    public const string ROUTE_NAME = 'log_csp';

    #[Route(path: '/csp', name: self::ROUTE_NAME)]
    public function invoke(Request $request, LoggerInterface $logger, MailerInterface $mailer): Response
    {
        $content = StringUtils::trim($request->getContent());
        if (null === $content) {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }

        try {
            $entries = StringUtils::decodeJson($content);
            if ([] !== $entries) {
                $message = $this->trans('notification.csp_title');
                foreach ($entries as $entry) {
                    $logger->error($message, $entry);
                }
                $this->sendNotification($mailer, $entries);
            }
        } catch (\InvalidArgumentException|TransportExceptionInterface $e) {
            $message = $this->trans('notification.csp_error');
            $context = $this->getExceptionContext($e);
            $logger->error($message, $context);
        } finally {
            return new Response(status: Response::HTTP_NO_CONTENT);
        }
    }

    private function getActionUrl(): string
    {
        return $this->generateUrl(route: self::HOME_PAGE, referenceType: UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * @throws TransportExceptionInterface
     */
    private function sendNotification(MailerInterface $mailer, array $entries): void
    {
        $address = ApplicationService::getOwnerAddress();
        $notification = NotificationEmail::instance($this->getTranslator(), 'notification/csp_violation.html.twig')
            ->subject($this->trans('notification.csp_title'))
            ->from($address)
            ->to($address)
            ->context(['entries' => $entries])
            ->action($this->trans('index.title'), $this->getActionUrl())
            ->importance(Importance::HIGH);

        $mailer->send($notification);
    }
}
