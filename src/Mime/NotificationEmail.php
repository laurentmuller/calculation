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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends NotificationEmail to use translated subject.
 */
class NotificationEmail extends \Symfony\Bridge\Twig\Mime\NotificationEmail
{
    use TranslatorTrait;

    private ?string $footerText = null;

    public function __construct(TranslatorInterface $translator, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->translator = $translator;
        $this->htmlTemplate('emails/notification.html.twig');
    }

    /**
     * Adds the given uploaded file as attachment. Do nothing if the file is null or not valid.
     */
    public function attachFromUploadedFile(?UploadedFile $file): static
    {
        if (null !== $file && $file->isValid()) {
            $path = $file->getPathname();
            $name = $file->getClientOriginalName();
            $type = $file->getClientMimeType();

            return $this->attachFromPath($path, $name, $type);
        }

        return $this;
    }

    public function getContext(): array
    {
        $context = parent::getContext();
        if (!empty($this->footerText)) {
            return \array_merge($context, ['footer_text' => $this->footerText]);
        }

        return $context;
    }

    public function getPreparedHeaders(): Headers
    {
        $headers = parent::getPreparedHeaders();
        $subject = $headers->get('Subject');
        if (null !== $subject) {
            $subject->setBody($this->translateSubject());
        } else {
            $headers->addTextHeader('Subject', $this->translateSubject());
        }

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
