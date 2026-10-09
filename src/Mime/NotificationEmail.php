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
use App\Service\ApplicationService;
use Symfony\Bridge\Twig\Mime\NotificationEmail as BaseNotificationEmail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the NotificationEmail class with a translatable subject or importance, and a custom footer.
 */
class NotificationEmail extends BaseNotificationEmail
{
    final public function __construct(
        private readonly TranslatorInterface $translator,
        private Importance $importance = Importance::DEFAULT,
        private bool $signature = true
    ) {
        parent::__construct();
        if (Importance::DEFAULT !== $importance) {
            parent::importance($importance->value);
        }
    }

    /**
     * Adds the given uploaded file as an attachment.
     *
     * Do nothing if the file is null or not valid.
     */
    public function attachFromUploadedFile(?UploadedFile $file): static
    {
        if ($file instanceof UploadedFile && $file->isValid()) {
            return $this->attachFromPath(
                path: $file->getPathname(),
                name: $file->getClientOriginalName(),
                contentType: $file->getClientMimeType()
            );
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

    #[\Override]
    public function getContext(): array
    {
        return parent::getContext() + [
            'importance_title' => $this->getImportanceTitle(),
            'signature' => $this->signature,
        ];
    }

    public function getImportanceTitle(): string
    {
        return $this->importance->transTitle($this->translator);
    }

    #[\Override]
    public function getPreparedHeaders(): Headers
    {
        $body = \sprintf(
            '%s - %s',
            $this->getSubject(),
            $this->getImportanceTitle()
        );

        $headers = parent::getPreparedHeaders();
        $headers->setHeaderBody(
            type: 'Text',
            name: 'Subject',
            body: $body
        );

        return $headers;
    }

    #[\Override]
    public function getSubject(): string
    {
        return parent::getSubject() ?? ApplicationService::APP_FULL_NAME;
    }

    /**
     * @throws \InvalidArgumentException if the importance is a string and cannot be translated to a corresponding
     *                                   Importance enumeration
     */
    #[\Override]
    public function importance(Importance|string $importance): static
    {
        if (\is_string($importance)) {
            try {
                $importance = Importance::from($importance);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException(\sprintf('Invalid importance value: "%s".', $importance), $e->getCode(), $e);
            }
        }
        $this->importance = $importance;

        return parent::importance($importance->value);
    }

    /**
     * Creates a new instance.
     */
    public static function instance(
        TranslatorInterface $translator,
        string $htmlTemplate = 'notification/notification.html.twig',
        string $textTemplate = 'notification/notification.txt.twig',
    ): static {
        return (new static($translator))
            ->htmlTemplate($htmlTemplate)
            ->textTemplate($textTemplate);
    }

    public function isSignature(): bool
    {
        return $this->signature;
    }

    public function setSignature(bool $signature): static
    {
        $this->signature = $signature;

        return $this;
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
