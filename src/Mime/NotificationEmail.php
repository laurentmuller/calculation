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
    private ?TranslatorInterface $translator = null;

    public function __construct(TranslatorInterface $translator = null, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->htmlTemplate('emails/notification.html.twig');
        $this->translator = $translator;
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();
        $headers->setHeaderBody('Text', 'Subject', $this->translateSubject());

        return $headers;
    }

    /**
     * Sets the translator used to translate the subject.
     */
    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    private function translateSubject(): ?string
    {
        $subject = $this->getSubject();
        if ($this->translator) {
            $importance = $this->getContext()['importance'] ?? self::IMPORTANCE_LOW;
            $translated = $this->translator->trans("importance.full.$importance");

            return \sprintf('%s - %s', $subject, $translated);
        }

        return $subject;
    }
}
