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
use App\Traits\RightsTrait;
use App\Traits\RoleTrait;
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
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * User.
 *
 * @author Laurent Muller
 *
 * @ORM\Table(name="sy_User", uniqueConstraints={
 *     @ORM\UniqueConstraint(name="unique_user_email", columns={"email"}),
 *     @ORM\UniqueConstraint(name="unique_user_username", columns={"username"})
 * })
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="email.already_used")
 * @UniqueEntity(fields={"username"}, message="username.already_used")
 * @Vich\Uploadable
 */
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface, RoleInterface, ResetPasswordRequestInterface
{
    use RightsTrait;
    use RoleTrait;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Length(max=180)
     * @Assert\NotBlank
     * @Assert\Email
     */
    private ?string $email = null;

    /**
     * @ORM\Column(type="boolean", options={"default" = 1})
     */
    private bool $enabled = true;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeInterface $expiresAt = null;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     * @Assert\Length(max=100)
     */
    private ?string $hashedToken = null;

    /**
     * The image file. NB: This is not a mapped field of entity metadata, just a simple property.
     *
     * @Vich\UploadableField(mapping="user_image", fileNameProperty="imageName")
     * @Assert\Image(maxSize="10485760")
     */
    private ?File $imageFile = null;

    /**
     * The image file name.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     */
    private ?string $imageName = null;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeInterface $lastLogin = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\Length(max=255)
     * @Assert\NotBlank
     */
    private ?string $password = null;

    /**
     * The properties.
     *
     * @var Collection<int, UserProperty>
     * @ORM\OneToMany(targetEntity=UserProperty::class, mappedBy="user", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $properties;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeInterface $requestedAt = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private ?string $selector = null;

    /**
     * The last updated date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     * @Assert\Length(max=180)
     * @Assert\NotBlank
     */
    private ?string $username = null;

    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
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
     * @param array{
     *      id: int|null,
     *      username: string|null,
     *      password: string|null
     *     } $data
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
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
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
     * {@inheritdoc}
     *
     * @see UserInterface
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
            $this->updatedAt = new \DateTime();
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
     * @param \DateTimeInterface $expiresAt   the expiration date
     * @param string             $selector    a non-hashed random string used to fetch a request from persistence
     * @param string             $hashedToken the hashed token used to verify a reset request
     */
    public function setResetPasswordRequest(\DateTimeInterface $expiresAt, string $selector, string $hashedToken): self
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
