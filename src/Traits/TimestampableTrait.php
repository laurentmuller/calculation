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

namespace App\Traits;

use App\Util\FormatUtils;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Trait to implement the ORM TimestampableInterface.
 *
 * @author Laurent Muller
 *
 * @see App\Interfaces\TimestampableInterface
 */
trait TimestampableTrait
{
    /**
     * The creation date.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeInterface $createdAt = null;

    /**
     * The creation user name.
     *
     * @ORM\Column(nullable=true)
     */
    protected ?string $createdBy = null;

    /**
     * The updated date.
     *
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    protected ?\DateTimeInterface $updatedAt = null;

    /**
     * The updated user name.
     *
     * @ORM\Column(nullable=true)
     */
    protected ?string $updatedBy = null;

    /**
     * Gets the creation date.
     */
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Gets the creation user name.
     */
    public function getCreatedBy(): ?string
    {
        return $this->createdBy;
    }

    /**
     * Gets the text for the created date and user name.
     */
    public function getCreatedText(TranslatorInterface $translator, bool $short = false): string
    {
        $id = $short ? 'common.entity_created_short' : 'common.entity_created';

        return $this->formatDateAndUser($this->createdAt, $this->createdBy, $translator, $id);
    }

    /**
     * Gets the updated date.
     */
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    /**
     * Gets the updated user name.
     */
    public function getUpdatedBy(): ?string
    {
        return $this->updatedBy;
    }

    /**
     * Gets the text for the updated date and user name.
     */
    public function getUpdatedText(TranslatorInterface $translator, bool $short = false): string
    {
        $id = $short ? 'common.entity_updated_short' : 'common.entity_updated';

        return $this->formatDateAndUser($this->updatedAt, $this->updatedBy, $translator, $id);
    }

    /**
     * Sets the creation date.
     */
    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Sets the creation user name.
     */
    public function setCreatedBy(string $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    /**
     * Sets the updated date and user name.
     */
    public function setUpdated(\DateTimeInterface $updatedAt, string $updatedBy): self
    {
        return $this->setUpdatedAt($updatedAt)->setUpdatedBy($updatedBy);
    }

    /**
     * Sets the updated date.
     */
    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Sets the updated user name.
     */
    public function setUpdatedBy(string $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    /**
     * Format the date and user.
     */
    private function formatDateAndUser(?\DateTimeInterface $date, ?string $user, TranslatorInterface $translator, string $id): string
    {
        $date = null !== $date ? FormatUtils::formatDateTime($date) : $translator->trans('common.empty_date');
        if (null === $user || '' === $user) {
            $user = $translator->trans('common.empty_user');
        }

        return $translator->trans($id, [
            '%date%' => $date,
            '%user%' => $user,
        ]);
    }
}
