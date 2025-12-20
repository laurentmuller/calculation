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

use App\Entity\User;
use App\Enums\Importance;
use App\Utils\StringUtils;
use Symfony\Bridge\Twig\Mime\NotificationEmail as BaseNotificationEmail;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Header\HeaderInterface;
use Symfony\Component\Mime\Header\Headers;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Extends the NotificationEmail class with the translated subject and custom footer.
 *
 * Each address parameter can be also a user.
 */
class NotificationEmail extends BaseNotificationEmail
{
    private ?string $importance = null;

    final public function __construct(private readonly TranslatorInterface $translator)
    {
        parent::__construct();
    }

    #[\Override]
    public function addBcc(string|Address|User ...$addresses): static
    {
        return parent::addBcc(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function addCc(string|Address|User ...$addresses): static
    {
        return parent::addCc(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function addFrom(string|Address|User ...$addresses): static
    {
        return parent::addFrom(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function addReplyTo(string|Address|User ...$addresses): static
    {
        return parent::addReplyTo(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function addTo(string|Address|User ...$addresses): static
    {
        return parent::addTo(...$this->convertAddresses(...$addresses));
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

    #[\Override]
    public function bcc(string|Address|User ...$addresses): static
    {
        return parent::bcc(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function cc(string|Address ...$addresses): static
    {
        return parent::cc(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function from(string|Address|User ...$addresses): static
    {
        return parent::from(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function getContext(): array
    {
        $context = parent::getContext();
        if (StringUtils::isString($this->importance)) {
            $context['importance_text'] = $this->importance;
        }

        return $context;
    }

    #[\Override]
    public function getPreparedHeaders(): Headers
    {
        $subject = $this->getSubject();
        $headers = parent::getPreparedHeaders();
        if (!StringUtils::isString($subject) || !StringUtils::isString($this->importance)) {
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
     * @phpstan-param Importance|self::IMPORTANCE_* $importance
     *
     * @throws \InvalidArgumentException if the importance is a string and cannot be translated to the corresponding enumeration
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
        $this->importance = $importance->translateTitle($this->translator);

        return parent::importance($importance->value);
    }

    /**
     * Creates a new instance.
     *
     * @phpstan-param non-empty-string $template
     */
    public static function instance(
        TranslatorInterface $translator,
        string $template = 'notification/notification.html.twig'
    ): static {
        return (new static($translator))->htmlTemplate($template);
    }

    #[\Override]
    public function replyTo(string|Address|User ...$addresses): static
    {
        return parent::replyTo(...$this->convertAddresses(...$addresses));
    }

    #[\Override]
    public function subject(string|TranslatableMessage $subject): static
    {
        if ($subject instanceof TranslatableMessage) {
            $subject = $subject->trans($this->translator);
        }

        return parent::subject($subject);
    }

    #[\Override]
    public function to(string|Address|User ...$addresses): static
    {
        return parent::to(...$this->convertAddresses(...$addresses));
    }

    /**
     * @return array<string|Address>
     */
    private function convertAddresses(string|Address|User ...$addresses): array
    {
        return \array_map(
            static fn (string|Address|User $address): string|Address => $address instanceof User ? $address->getAddress() : $address,
            $addresses
        );
    }
}
