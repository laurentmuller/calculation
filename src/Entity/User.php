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
use App\Utils\StringUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\DatePointType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Mime\Address;
use Symfony\Component\Serializer\Attribute\Ignore;
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

    /** Gets email address. */
    public Address $address {
        get => new Address((string) $this->email, $this->getUserIdentifier());
    }

    /** The email. */
    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: self::MAX_USERNAME_LENGTH, unique: true)]
    public ?string $email = null {
        set => StringUtils::trim($value);
    }

    /** The enable state. */
    #[ORM\Column(options: ['default' => true])]
    public bool $enabled = true;

    /** The image file. NB: This is not a mapped field of entity metadata, just a simple property. */
    #[Assert\Image(maxSize: 10_485_760)]
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'imageName')]
    public ?File $imageFile = null {
        set {
            $this->imageFile = $value;
            if ($value instanceof File) {
                $this->updatedAt = DateUtils::createDatePoint();
            }
        }
    }

    /** The image file name. */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    public ?string $imageName = null;

    /** Gets the initials (2 first upper letters). */
    public string $initials {
        get => \strtoupper(StringUtils::left($this->getUserIdentifier(), 2));
    }

    /** The last login. */
    #[ORM\Column(type: DatePointType::NAME, nullable: true)]
    public ?DatePoint $lastLogin = null;

    /** Gets the username and e-mail. */
    public string $nameAndEmail {
        get => \sprintf('%s (%s)', $this->getUserIdentifier(), $this->email);
    }

    /** The password. */
    #[Ignore]
    #[Assert\NotBlank]
    #[ORM\Column]
    public ?string $password = null;

    /**
     * The user properties.
     *
     * @var Collection<int, UserProperty>
     */
    #[ORM\OneToMany(
        targetEntity: UserProperty::class,
        mappedBy: 'user',
        cascade: ['persist', 'remove'],
        fetch: self::EXTRA_LAZY,
        orphanRemoval: true
    )]
    public Collection $properties;

    /** The username. */
    #[Assert\NotBlank]
    #[Assert\NoSuspiciousCharacters]
    #[Assert\Length(min: self::MIN_USERNAME_LENGTH, max: self::MAX_USERNAME_LENGTH)]
    #[ORM\Column(length: self::MAX_USERNAME_LENGTH, unique: true)]
    public ?string $username = null {
        set => StringUtils::trim($value);
    }

    /** The verified state. */
    #[ORM\Column(options: ['default' => false])]
    public bool $verified = false;

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
        // @phpstan-ignore assign.propertyReadOnly
        [$this->id, $this->username, $this->password] = $data;
    }

    /**
     * Add a property.
     */
    public function addProperty(UserProperty $property): self
    {
        if (!$this->contains($property)) {
            $this->properties->add($property);
            $property->user = $this;
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

    #[\Override]
    public function getDisplay(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Gets the absolute image path, if any.
     */
    public function getImagePath(StorageInterface $storage): ?string
    {
        try {
            $path = $storage->resolvePath($this);
            if (null !== $path && \is_file($path)) {
                return $path;
            }
        } catch (MappingNotFoundException) {
            // ignore
        }

        return null;
    }

    #[\Override]
    public function getPassword(): ?string
    {
        return $this->password;
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
        /** @phpstan-var non-empty-string */
        return (string) $this->username;
    }

    /**
     * Remove a property.
     */
    public function removeProperty(UserProperty $property): self
    {
        if ($this->properties->removeElement($property) && $property->user === $this) {
            $property->user = null;
        }

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

    /**
     * Sets the e-mail.
     */
    public function withEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Sets the enabled state.
     */
    public function withEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Sets image file.
     */
    public function withImageFile(?File $imageFile = null): self
    {
        $this->imageFile = $imageFile;

        return $this;
    }

    /**
     * Sets the image name.
     */
    public function withImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Sets the password.
     */
    public function withPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Sets the username.
     */
    public function withUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Sets the verified state.
     */
    public function withVerified(bool $verified): self
    {
        $this->verified = $verified;

        return $this;
    }
}
