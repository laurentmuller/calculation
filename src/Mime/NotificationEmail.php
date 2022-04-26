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

namespace App\Mime;

use App\Traits\TranslatorTrait;
use Symfony\Bridge\Twig\Mime\NotificationEmail as BaseNotificationEmail;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends NotificationEmail to use translated subjet.
 */
class NotificationEmail extends BaseNotificationEmail
{
    use TranslatorTrait;

    private ?string $footerText = null;

    public function __construct(?TranslatorInterface $translator, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->htmlTemplate('emails/notification.html.twig');
        if (null !== $translator) {
            $this->setTranslator($translator);
        }
    }

    public function getContext(): array
    {
        if (null !== $this->footerText) {
            return \array_merge([
                'footer_text' => $this->footerText,
            ], parent::getContext());
        }

        return parent::getContext();
    }

    /**
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
        $this->footerText = $footerText;

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
