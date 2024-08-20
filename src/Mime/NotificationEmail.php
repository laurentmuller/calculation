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
use Symfony\Bridge\Twig\Mime\NotificationEmail as BaseNotificationEmail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Mime\Part\AbstractPart;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the NotificationEmail class with the translated subject and custom footer.
 *
 * @psalm-consistent-constructor
 */
class NotificationEmail extends BaseNotificationEmail
{
    private ?string $importance = null;

    public function __construct(?Headers $headers = null, ?AbstractPart $body = null)
    {
        parent::__construct($headers, $body);
        $this->htmlTemplate('notification/notification.html.twig');
    }

    /**
     * Adds the given uploaded file as an attachment.
     *
     * Do nothing if the file is null or not valid.
     */
    public function attachFromUploadedFile(?UploadedFile $file): static
    {
        if ($file instanceof UploadedFile && $file->isValid()) {
            $path = $file->getPathname();
            $name = $file->getClientOriginalName();
            $type = $file->getClientMimeType();

            return $this->attachFromPath($path, $name, $type);
        }

        return $this;
    }

    /**
     * Adds the given uploaded files as attachments.
     *
     * @see NotificationEmail::attachFromUploadedFile()
     */
    public function attachFromUploadedFiles(?UploadedFile ...$files): static
    {
        foreach ($files as $file) {
            $this->attachFromUploadedFile($file);
        }

        return $this;
    }

    public static function create(?Headers $headers = null, ?AbstractPart $body = null): static
    {
        return new static($headers, $body);
    }

    public function getContext(): array
    {
        $context = parent::getContext();
        if (null !== $this->importance) {
            $context['importance_text'] = $this->importance;
        }

        return $context;
    }

    public function getPreparedHeaders(): Headers
    {
        $subject = $this->getSubject();
        $headers = parent::getPreparedHeaders();
        if (null === $subject || null === $this->importance) {
            return $headers;
        }

        $header = $headers->get('Subject');
        if ($header instanceof HeaderInterface) {
            $content = \sprintf('%s - %s', $subject, $this->importance);
            $header->setBody($content);
        }

        return $headers;
    }

    /**
     * Set translated importance.
     */
    public function updateImportance(Importance $importance, TranslatorInterface $translator): static
    {
        $this->importance = $translator->trans($importance->getReadableFull());

        return parent::importance($importance->value);
    }
}
