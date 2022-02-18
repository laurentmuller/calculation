<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Mime;

use App\Traits\TranslatorTrait;
use Symfony\Bridge\Twig\Mime\NotificationEmail as BaseNotificationEmail;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends NotificationEmail to use translated subjet.
 *
 * @author Laurent Muller
 */
class NotificationEmail extends BaseNotificationEmail
{
    use TranslatorTrait;

    public function __construct(TranslatorInterface $translator = null, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->htmlTemplate('emails/notification.html.twig');
        if (null !== $translator) {
            $this->setTranslator($translator);
        }
    }

    /**
     * {@inheritDoc}
     *
     * @psalm-suppress InternalMethod
     */
    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();
        $headers->setHeaderBody('Text', 'Subject', $this->translateSubject());

        return $headers;
    }

    /**
     * Sets the footer text.
     */
    public function setFooterText(string $footerText): self
    {
        $context = $this->getContext();
        $context['footer_text'] = $footerText;
        $this->context($context);
        //return $this->updateContext('footer_text', $footerText);
        return $this;
    }

    private function translateSubject(): string
    {
        $subject = (string) $this->getSubject();
        $importance = (string) ($this->getContext()['importance'] ?? self::IMPORTANCE_LOW);
        $translated = $this->trans("importance.full.$importance");

        return \sprintf('%s - %s', $subject, $translated);
    }
}
