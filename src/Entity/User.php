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

use App\Interfaces\RoleInterface;
use App\Repository\UserRepository;
use App\Traits\RightsTrait;
use App\Traits\RoleTrait;
use App\Util\FileUtils;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\Address;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use Vich\UploaderBundle\Exception\MappingNotFoundException;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'sy_User')]
#[ORM\UniqueConstraint(name: 'unique_user_email', columns: ['email'])]
#[ORM\UniqueConstraint(name: 'unique_user_username', columns: ['username'])]
#[UniqueEntity(fields: ['email'], message: 'email.already_used')]
#[UniqueEntity(fields: ['username'], message: 'username.already_used')]
#[Vich\Uploadable]
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface, RoleInterface, ResetPasswordRequestInterface
{
    use RightsTrait;
    use RoleTrait;

    #[Assert\Email]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $enabled = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $hashedToken = null;

    /**
     * The image file. NB: This is not a mapped field of entity metadata, just a simple property.
     */
    #[Assert\Image(maxSize: 10485760)]
    #[Vich\UploadableField(mapping: 'user_image', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    /**
     * The image file name.
     */
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column(nullable: true)]
    private ?string $imageName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: self::MAX_STRING_LENGTH)]
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, UserProperty>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserProperty::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $properties;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $requestedAt = null;

    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $selector = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $verified = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->properties = new ArrayCollection();
    }

    /**
     * @return array{
     *      id: int|null,
     *      username: string|null,
     *      password: string|null}
     */
    public function __serialize(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'password' => $this->password,
        ];
    }

    /**
     * @param array{id: int|null, username: string|null, password: string|null} $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = $data['id'];
        $this->username = $data['username'];
        $this->password = $data['password'];
    }

    /**
     * Add a property.
     */
    public function addProperty(UserProperty $property): self
    {
        if (!$this->contains($property)) {
            $this->properties[] = $property;
            $property->setUser($this);
        }

        return $this;
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
     * {@inheritdoc}
     *
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
    }

    /**
     * Removes the reset password request values.
     */
    public function eraseResetPasswordRequest(): self
    {
        $this->requestedAt = null;
        $this->expiresAt = null;
        $this->selector = null;
        $this->hashedToken = null;

        return $this;
    }

    /**
     * Gets the address (email and name) used for send email.
     */
    public function getAddress(): Address
    {
        return new Address((string) $this->email, (string) $this->username);
    }

    /**
     * Gets the URL of the avatar image.
     *
     * @param int $size       the image size (only used if the value is greater than 0)
     * @param int $set        the image set (only used if the value is between 2 and 5 inclusive)
     * @param int $background the background set (only used if the value is between 1 and 2 inclusive)
     *
     * @see https://robohash.org/
     */
    public function getAvatar(int $size = 32, int $set = 0, int $background = 0): string
    {
        $query = [];
        if ($size > 0) {
            $query['size'] = \sprintf('%dx%d', $size, $size);
        }
        if ($set >= 2 && $set <= 5) {
            $query['set'] = \sprintf('set%d', $set);
        }
        if ($background >= 1 && $background <= 2) {
            $query['bgset'] = \sprintf('bg%d', $background);
        }

        $url = 'https://robohash.org/' . \urlencode($this->getUserIdentifier());
        if (!empty($query)) {
            return $url . '?' . \http_build_query($query);
        }

        return $url;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Gets the e-mail.
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestInterface
     */
    public function getExpiresAt(): \DateTimeInterface
    {
        return $this->expiresAt ?? new \DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestInterface
     */
    public function getHashedToken(): string
    {
        return (string) $this->hashedToken;
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
                $path = $storage->resolvePath($this, 'imageFile');
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
     * Gets the date of the last login.
     */
    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    /**
     * Gets the username and e-mail.
     */
    public function getNameAndEmail(): string
    {
        return \sprintf('%s <%s>', $this->getUserIdentifier(), (string) $this->getEmail());
    }

    /**
     * {@inheritdoc}
     *
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return (string) $this->password;
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
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestInterface
     */
    public function getRequestedAt(): \DateTimeInterface
    {
        return $this->requestedAt ?? new \DateTimeImmutable();
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestInterface
     */
    public function getUser(): object
    {
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * Gets username.
     */
    public function getUsername(): string
    {
        return $this->getUserIdentifier();
    }

    /**
     * Returns a value indicating if this user is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * {@inheritdoc}
     *
     * @see ResetPasswordRequestInterface
     */
    public function isExpired(): bool
    {
        return null === $this->expiresAt || $this->expiresAt->getTimestamp() <= \time();
    }

    /**
     * Returns a value indicating if the reset password was requested.
     */
    public function isResetPassword(): bool
    {
        return null !== $this->hashedToken;
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
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|null $imageFile the file
     * @param bool      $update    true to update the modification date
     */
    public function setImageFile(?File $imageFile = null, bool $update = true): self
    {
        $this->imageFile = $imageFile;
        if ($update) {
            $this->updatedAt = new \DateTimeImmutable();
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
     * Sets the reset password request values.
     *
     * @param string $selector    a non-hashed random string used to fetch a request from persistence
     * @param string $hashedToken the hashed token used to verify a reset request
     */
    public function setResetPasswordRequest(\DateTimeImmutable $expiresAt, string $selector, string $hashedToken): self
    {
        $this->requestedAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;

        return $this;
    }

    /**
     * Sets the username.
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
     * Sets the last login date to now.
     */
    public function updateLastLogin(): self
    {
        $this->lastLogin = new \DateTimeImmutable();

        return $this;
    }
}
