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

namespace App\Traits;

use App\Interfaces\UserInterface;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Trait for class implementing the TimestampableInterface interface.
 *
 * @psalm-require-implements \App\Interfaces\TimestampableInterface
 */
trait TimestampableTrait
{
    /**
     * The creation date.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    /**
     * The creation username.
     */
    #[Assert\Length(max: UserInterface::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: UserInterface::MAX_USERNAME_LENGTH, nullable: true)]
    private ?string $createdBy = null;

    /**
     * The updated date.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * The updated username.
     */
    #[Assert\Length(max: UserInterface::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: UserInterface::MAX_USERNAME_LENGTH, nullable: true)]
    private ?string $updatedBy = null;

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    public function getCreatedMessage(bool $short = false): TranslatableMessage
    {
        $content = $this->getContentMessage($this->createdAt, $this->createdBy);
        if ($short) {
            return $content;
        }

        return new TranslatableMessage('common.entity_created', ['%content%' => $content]);
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    public function getUpdatedMessage(bool $short = false): TranslatableMessage
    {
        $content = $this->getContentMessage($this->updatedAt, $this->updatedBy);
        if ($short) {
            return $content;
        }

        return new TranslatableMessage('common.entity_updated', ['%content%' => $content]);
    }

    /**
     * Update the created and modified date and user.
     */
    public function updateTimestampable(\DateTimeImmutable $date, string $user): bool
    {
        $changed = false;
        if ($this->isNew()) {
            if (null === $this->createdAt) {
                $this->createdAt = $date;
                $changed = true;
            }
            if (null === $this->createdBy) {
                $this->createdBy = $user;
                $changed = true;
            }
        }
        if ($this->updatedAt !== $date) {
            $this->updatedAt = $date;
            $changed = true;
        }
        if ($this->updatedBy !== $user) {
            $this->updatedBy = $user;
            $changed = true;
        }

        return $changed;
    }

    private function getContentMessage(?\DateTimeImmutable $date, ?string $user): TranslatableMessage
    {
        if (!$date instanceof \DateTimeImmutable && null === $user) {
            return new TranslatableMessage('common.entity_empty');
        }

        return new TranslatableMessage('common.entity_date_user', [
            '%date%' => $this->getDateMessage($date),
            '%user%' => $this->getUserMessage($user),
        ]);
    }

    private function getDateMessage(?\DateTimeImmutable $date): string|TranslatableMessage
    {
        if ($date instanceof \DateTimeImmutable) {
            return FormatUtils::formatDateTime($date);
        }

        return new TranslatableMessage('common.entity_empty_date');
    }

    private function getUserMessage(?string $user): string|TranslatableMessage
    {
        if (StringUtils::isString($user)) {
            return $user;
        }

        return new TranslatableMessage('common.entity_empty_user');
    }
}
