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

namespace App\Entity;

use App\Interfaces\ComparableInterface;
use App\Interfaces\TimestampableInterface;
use App\Interfaces\UserInterface;
use App\Repository\UserRepository;
use App\Traits\ResetPasswordRequestTrait;
use App\Traits\RoleTrait;
use App\Traits\TimestampableTrait;
use App\Utils\DateUtils;
use App\Utils\FileUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Exception\MappingNotFoundException;
use Vich\UploaderBundle\Mapping\Attribute as Vich;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * User.
 *
 * @implements ComparableInterface<User>
 */
#[ORM\Table(name: 'sy_User')]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'unique_user_username', columns: ['username'])]
#[UniqueEntity(fields: 'email', message: 'user.unique_email')]
#[UniqueEntity(fields: 'username', message: 'user.unique_username')]
#[Vich\Uploadable]
class User extends AbstractEntity implements ComparableInterface, TimestampableInterface, UserInterface
{
    use ResetPasswordRequestTrait;
    use RoleTrait;
    use TimestampableTrait;

    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: self::MAX_USERNAME_LENGTH, unique: true)]
    private ?string $email = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    /**
     * The image file. NB: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Assert\Image(maxSize: 10_485_760)]
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    /**
     * The image file name.
     */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(type: DatePointType::NAME, nullable: true)]
    private ?DatePoint $lastLogin = null;

    #[Assert\NotBlank]
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, UserProperty>
     *
     * @phpstan-var ArrayCollection<int, UserProperty>
     */
    #[ORM\OneToMany(
        targetEntity: UserProperty::class,
        mappedBy: 'user',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    private Collection $properties;

    #[Assert\NotBlank]
    #[Assert\NoSuspiciousCharacters]
    #[Assert\Length(min: self::MIN_USERNAME_LENGTH, max: self::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: self::MAX_USERNAME_LENGTH, unique: true)]
    private ?string $username = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $verified = false;

    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    /**
     * @return array{int|null, string|null, string|null}
     */
    public function __serialize(): array
    {
        return [$this->id, $this->username, $this->password];
    }

    /**
     * @param array{int|null, string|null, string|null} $data
     */
    public function __unserialize(array $data): void
    {
        [$this->id, $this->username, $this->password] = $data;
    }

    /**
     * Add a property.
     */
    public function addProperty(UserProperty $property): self
    {
        if (!$this->contains($property)) {
            $this->properties->add($property);
            $property->setUser($this);
        }

        return $this;
    }

    #[\Override]
    public function compare(ComparableInterface $other): int
    {
        return \strnatcasecmp($this->getUserIdentifier(), $other->getUserIdentifier());
    }

    /**
     * Checks whether the given property is contained within this collection of properties.
     *
     * @param UserProperty $property the property to search for
     *
     * @return bool true if this collection contains the property, false otherwise
     */
    public function contains(UserProperty $property): bool
    {
        return $this->properties->contains($property);
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    #[\Deprecated]
    public function eraseCredentials(): void
    {
    }

    /**
     * Gets email address.
     */
    public function getAddress(): Address
    {
        return new Address((string) $this->email, (string) $this->username);
    }

    #[\Override]
    public function getDisplay(): string
    {
        return $this->getUsername();
    }

    /**
     * Gets the e-mail.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Gets the image file.
     *
     * @return File|UploadedFile
     */
    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    /**
     * Gets the image name.
     */
    public function getImageName(): ?string
    {
        return $this->imageName;
    }

    /**
     * Gets the absolute image path, if any.
     */
    public function getImagePath(StorageInterface $storage): ?string
    {
        try {
            if (null !== $this->imageName) {
                $path = $storage->resolvePath($this);
                if (null !== $path && FileUtils::isFile($path)) {
                    return $path;
                }
            }
        } catch (MappingNotFoundException) {
            // ignore
        }

        return null;
    }

    /**
     * Gets the initials (2 first upper letters).
     */
    public function getInitials(): string
    {
        return \strtoupper(\substr((string) $this->username, 0, 2));
    }

    /**
     * Gets the date of the last login.
     */
    public function getLastLogin(): ?DatePoint
    {
        return $this->lastLogin;
    }

    /**
     * Gets the username and e-mail.
     */
    public function getNameAndEmail(): string
    {
        return \sprintf('%s (%s)', $this->getUserIdentifier(), $this->getEmail());
    }

    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
    }

    /**
     * Get the properties.
     *
     * @return Collection<int, UserProperty>
     */
    public function getProperties(): Collection
    {
        return $this->properties;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @return non-empty-string
     *
     * @see UserInterface
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * Gets username.
     *
     * @return non-empty-string
     */
    public function getUsername(): string
    {
        /** @phpstan-var non-empty-string */
        return (string) $this->username;
    }

    /**
     * Returns a value indicating if this user is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Returns a value indicating if this user is verified.
     */
    public function isVerified(): bool
    {
        return $this->verified;
    }

    /**
     * Remove a property.
     */
    public function removeProperty(UserProperty $property): self
    {
        if ($this->properties->removeElement($property) && $property->getUser() === $this) {
            $property->setUser(null);
        }

        return $this;
    }

    /**
     * Sets the e-mail.
     */
    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Sets the enabled state.
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Sets image file.
     *
     * If manually uploading a file (i.e., not using Symfony Form), ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param ?File $imageFile the file
     * @param bool  $update    true to update the modification date
     */
    public function setImageFile(?File $imageFile = null, bool $update = true): self
    {
        $this->imageFile = $imageFile;
        if ($update) {
            $this->updatedAt = DateUtils::createDatePoint();
        }

        return $this;
    }

    /**
     * Sets the image name.
     */
    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Sets the password.
     */
    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Sets the username.
     *
     * @param non-empty-string $username
     */
    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Sets the verified state.
     */
    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }

    /**
     * Sets the last login date as now.
     */
    public function updateLastLogin(): self
    {
        $this->lastLogin = DateUtils::createDatePoint();

        return $this;
    }
}
