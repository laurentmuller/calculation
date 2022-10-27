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

use App\Enums\Importance;
use App\Traits\TranslatorTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the NotificationEmail class with translated subject and custom footer.
 */
class NotificationEmail extends \Symfony\Bridge\Twig\Mime\NotificationEmail
{
    use TranslatorTrait;

    private ?string $footerText = null;

    public function __construct(private readonly TranslatorInterface $translator, Headers $headers = null, AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->htmlTemplate('notification/notification.html.twig');
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
            $context['footer_text'] = $this->footerText;
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
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    public function importance(string|Importance $importance): static
    {
        if ($importance instanceof Importance) {
            $importance = $importance->value;
        }

        return parent::importance($importance);
    }

    /**
     * Sets the footer text.
     */
    public function setFooterText(string $footerText): static
    {
        $this->footerText = $footerText;

        return $this;
    }

    /**
     * Update the footer text.
     *
     * @param string $appName the application name and version
     */
    public function updateFooterText(string $appName): static
    {
        $text = $this->trans('notification.footer', ['%app_name%' => $appName]);

        return $this->setFooterText($text);
    }

    private function translateImportance(): string
    {
        $importance = Importance::tryFrom((string) $this->getContext()['importance']) ?? Importance::LOW;

        return $this->trans($importance->getReadableFull());
    }

    private function translateSubject(): string
    {
        $subject = (string) $this->getSubject();
        $importance = $this->translateImportance();

        return \sprintf('%s - %s', $subject, $importance);
    }
}
