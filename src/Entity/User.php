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

namespace App\Entity;

use App\Interfaces\RoleInterface;
use App\Traits\RightsTrait;
use App\Traits\RoleTrait;
use App\Util\FormatUtils;
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
 * @ORM\Table(name="sy_User")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="email.already_used")
 * @UniqueEntity(fields={"username"}, message="username.already_used")
 * @Vich\Uploadable
 */
class User extends AbstractEntity implements UserInterface, PasswordAuthenticatedUserInterface, RoleInterface, ResetPasswordRequestInterface, \Serializable
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
     * @ORM\Column(type="datetime_immutable", nullable=true)
     */
    private ?\DateTimeInterface $requestedAt = null;

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private ?string $selector = null; // @phpstan-ignore-line

    /**
     * The last updated date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     */
    private ?\DateTimeInterface $updatedAt = null; // @phpstan-ignore-line

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
     * @param int $size       the image size (only used if the value is greather than 0)
     * @param int $set        the image set (only used if the value is between 2 to 5 inclusive)
     * @param int $background the background set (only used if the value is between 1 to 2 inclusive)
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
     * {@inheritDoc}
     */
    public function serialize(): string
    {
        return \serialize([
            $this->id,
            $this->username,
            $this->password,
        ]);
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
     * {@inheritDoc}
     */
    public function unserialize($serialized): void
    {
        [
            $this->id,
            $this->username,
            $this->password,
        ] = (array) \unserialize($serialized);
    }

    /**
     * Sets the last login date to now.
     */
    public function updateLastLogin(): self
    {
        $this->lastLogin = new \DateTimeImmutable();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSearchTerms(): array
    {
        return [
            $this->email,
            $this->username,
            FormatUtils::formatDateTime($this->lastLogin),
        ];
    }
}
