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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

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
    private ?\DateTimeInterface $createdAt = null;

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
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * The updated username.
     */
    #[Assert\Length(max: UserInterface::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: UserInterface::MAX_USERNAME_LENGTH, nullable: true)]
    private ?string $updatedBy = null;

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets the formatted text for the created date and username.
     */
    public function getCreatedText(TranslatorInterface $translator, bool $short = false): string
    {
        $id = $short ? 'common.entity_created_short' : 'common.entity_created';

        return $this->formatDateAndUser($this->createdAt, $this->createdBy, $translator, $id);
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * Gets the formatted text for the updated date and username.
     */
    public function getUpdatedText(TranslatorInterface $translator, bool $short = false): string
    {
        $id = $short ? 'common.entity_updated_short' : 'common.entity_updated';

        return $this->formatDateAndUser($this->updatedAt, $this->updatedBy, $translator, $id);
    }

    public function updateTimestampable(\DateTimeImmutable $date, string $user): bool
    {
        $changed = false;
        $id = $this->getId();
        if (null === $id || 0 === $id) {
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

    private function formatDateAndUser(?\DateTimeInterface $date, ?string $user, TranslatorInterface $translator, string $id): string
    {
        $date = $date instanceof \DateTimeInterface ? FormatUtils::formatDateTime($date) : $translator->trans('common.empty_date');
        if (!StringUtils::isString($user)) {
            $user = $translator->trans('common.empty_user');
        }

        return $translator->trans($id, ['%date%' => $date, '%user%' => $user]);
    }
}
