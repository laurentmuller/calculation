<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Entity;

use App\Interfaces\RoleInterface;
use App\Traits\DateFormatterTrait;
use App\Traits\RightsTrait;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Table(name="sy_User")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="fos_user.email.already_used")
 * @UniqueEntity(fields={"username"}, message="fos_user.username.already_used")
 * @Vich\Uploadable
 */
class User extends BaseEntity implements UserInterface, RoleInterface, ResetPasswordRequestInterface
{
    use DateFormatterTrait;
    use RightsTrait;

    /**
     * The administrator role name.
     */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * The super administrator role name.
     */
    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * The user role name.
     */
    public const ROLE_USER = 'ROLE_USER';

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     * @Assert\Email
     *
     * @var ?string
     */
    private $email;

    /**
     * @ORM\Column(type="boolean", options={"default": 1})
     *
     * @var bool
     */
    private $enabled;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var ?\DateTimeInterface
     */
    private $expiresAt;

    /**
     * @ORM\Column(type="string", length=100, nullable=true)
     *
     * @var ?string
     */
    private $hashedToken;

    /**
     * The image file.
     * <p>
     * NOTE: This is not a mapped field of entity metadata, just a simple property.
     * For mime type add: (mimeTypes={"image/png", "image/jpeg", "image/gif", "image/x-ms-bmp"}).
     * </p>.
     *
     * @Vich\UploadableField(mapping="user_image", fileNameProperty="imageName")
     * @Assert\Image(maxSize="10485760")
     *
     * @var File
     */
    private $imageFile;

    /**
     * The image file name.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    private $imageName;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var ?\DateTimeInterface
     */
    private $lastLogin;

    /**
     * The overwrite rights flag.
     *
     * @ORM\Column(type="boolean", options={"default": 0})
     *
     * @var bool
     */
    private $overwrite;

    /**
     * @ORM\Column(type="string")
     *
     * @var string
     */
    private $password;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     *
     * @var ?\DateTimeInterface
     */
    private $requestedAt;

    /**
     * TODO: Replace array by json.
     *
     * @ORM\Column(type="array")
     *
     * @var string[]
     */
    private $roles = [];

    /**
     * @ORM\Column(type="string", length=20, nullable=true)
     *
     * @var ?string
     */
    private $selector;

    /**
     * The last updated date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    private $updatedAt;

    /**
     * @ORM\Column(type="string", length=180, unique=true)
     *
     * @var string
     */
    private $username;

    /**
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    private $verified = false;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->setEnabled(true)
            ->setOverwrite(false);
    }

    public function __toString(): string
    {
        return (string) $this->getUsername();
    }

    /**
     * Ensures that only the ROLE_ADMIN or ROLE_SUPER_ADMIN is selected.
     */
    public function checkRoles(): self
    {
        if ($this->hasRole(static::ROLE_ADMIN) && $this->hasRole(static::ROLE_SUPER_ADMIN)) {
            $this->removeRole(static::ROLE_ADMIN);
        }

        return $this;
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
     * Gets the URL of the avatar image.
     *
     * @param int $size       the image size
     * @param int $set        the image set
     * @param int $background the background set
     *
     * @return string the avatar url
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

        $url = 'https://robohash.org/' . $this->getUsername();
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
        return $this->__toString();
    }

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
        return $this->expiresAt ?? new \DateTimeImmutable('now');
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
     *
     * @return string
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
     * Gets the user name and e-mail.
     */
    public function getNameAndEmail(): string
    {
        return \sprintf('%s <%s>', $this->getUsername(), $this->getEmail());
    }

    /**
     * {@inheritdoc}
     *
     * @see UserInterface
     */
    public function getPassword(): string
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
        return $this->requestedAt ?? new \DateTimeImmutable('now');
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function getRole(): string
    {
        $roles = $this->getRoles();

        return \count($roles) ? $roles[0] : static::ROLE_USER;
    }

    /**
     * {@inheritdoc}
     *
     * @see UserInterface
     */
    public function getRoles(): array
    {
        // ensure that the 'ROLE_USER' is set
        $roles = $this->roles;
        $roles[] = self::ROLE_USER;

        return \array_unique($roles);
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
     * {@inheritdoc}
     *
     * @see UserInterface
     */
    public function getUsername(): string
    {
        return (string) $this->username;
    }

    public function hasRole(string $role): bool
    {
        return \in_array($role, $this->getRoles(), true);
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

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
     * Gets a value indicating if this righs overwrite the default rights.
     *
     * @return bool true if overwrite, false to use the default rights
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
    }

    /**
     * {@inheritdoc}
     *
     * @see RoleInterface
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole(self::ROLE_SUPER_ADMIN);
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function setEnabled(bool $boolean): self
    {
        $this->enabled = (bool) $boolean;

        return $this;
    }

    /**
     * Sets image file.
     *
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the  update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|UploadedFile $imageFile the file
     * @param bool              $update    true to update the modification date
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
     *
     * @param string $imageName
     */
    public function setImageName(?string $imageName): self
    {
        $this->imageName = $imageName;

        return $this;
    }

    /**
     * Sets the date of the last login.
     */
    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    /**
     * Sets a value indicating if this righs overwrite the default rights.
     *
     * @param bool $overwrite true if overwrite, false to use the default rights
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

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
        $this->requestedAt = new \DateTimeImmutable('now');
        $this->expiresAt = $expiresAt;
        $this->selector = $selector;
        $this->hashedToken = $hashedToken;

        return $this;
    }

    /**
     * Sets role.
     *
     * @param string $role the role to set
     */
    public function setRole(?string $role): self
    {
        $role = $role ?: static::ROLE_USER;

        return $this->setRoles([$role]);
    }

    /**
     * Sets roles.
     *
     * @param string[] $roles the roles to set
     */
    public function setRoles(array $roles): self
    {
        $this->roles = \array_unique($roles);

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setVerified(bool $verified): self
    {
        $this->verified = $verified;

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
            $this->localeDateTime($this->lastLogin),
        ];
    }
}
