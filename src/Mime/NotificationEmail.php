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
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the NotificationEmail class with the translated subject and custom footer.
 *
 * @psalm-consistent-constructor
 */
class NotificationEmail extends BaseNotificationEmail
{
    private ?string $importance = null;

    public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();
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

    public static function create(
        TranslatorInterface $translator,
        string $template = 'notification/notification.html.twig'
    ): self {
        return (new self($translator))->htmlTemplate($template);
    }

    #[\Override]
    public function getContext(): array
    {
        $context = parent::getContext();
        if (null !== $this->importance) {
            $context['importance_text'] = $this->importance;
        }

        return $context;
    }

    #[\Override]
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

    #[\Override]
    public function importance(string|Importance $importance): static
    {
        if (\is_string($importance)) {
            $importance = Importance::tryFrom($importance) ?? $importance;
        }
        if ($importance instanceof Importance) {
            $value = $importance->value;
            $this->importance = $importance->transTitle($this->translator);
        } else {
            $value = $importance;
            $this->importance = null;
        }

        return parent::importance($value);
    }

    #[\Override]
    public function subject(string|TranslatableMessage $subject): static
    {
        if ($subject instanceof TranslatableMessage) {
            $subject = $subject->trans($this->translator);
        }

        return parent::subject($subject);
    }
}
