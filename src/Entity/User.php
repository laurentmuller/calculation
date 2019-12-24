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

use App\Traits\DateFormatterTrait;
use App\Traits\RightsTrait;
use App\Traits\SearchTrait;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * Represents an user.
 *
 * @ORM\Table(name="sy_User")
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"email"}, message="fos_user.email.already_used")
 * @UniqueEntity(fields={"username"}, message="fos_user.username.already_used")
 * @Vich\Uploadable
 */
class User extends BaseUser implements IEntity
{
    use DateFormatterTrait;
    use RightsTrait;
    use SearchTrait;

    /**
     * The administrator role.
     */
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    /**
     * The primary key identifier.
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @var int
     */
    protected $id;

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
    protected $imageFile;

    /**
     * The image file name.
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Length(max=255)
     *
     * @var string
     */
    protected $imageName;

    /**
     * The overwrite rights flag.
     *
     * @ORM\Column(type="boolean", options={"default": 0})
     *
     * @var bool
     */
    protected $overwrite;

    /**
     * The last updated date.
     *
     * @ORM\Column(type="datetime", nullable=true)
     *
     * @var \DateTime
     */
    protected $updatedAt;

    /**
     * Constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->setEnabled(true)
            ->setOverwrite(false);
    }

    /**
     * Ensures that only the ROLE_ADMIN or ROLE_SUPER_ADMIN is selected.
     */
    public function checkRoles(): self
    {
        if ($this->hasRole(self::ROLE_ADMIN) && $this->hasRole(self::ROLE_SUPER_ADMIN)) {
            $this->removeRole(self::ROLE_ADMIN);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDisplay(): string
    {
        return $this->__toString();
    }

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id ? (int) $this->id : null;
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
     * Gets the user name and e-mail.
     */
    public function getNameAndEmail(): string
    {
        return \sprintf('%s <%s>', $this->getUsername(), $this->getEmail());
    }

    /**
     * Gets the role. This function is used to select the first role.
     */
    public function getRole(): string
    {
        $roles = $this->getRoles();

        return \count($roles) ? $roles[0] : self::ROLE_DEFAULT;
    }

    /**
     * Tells if this user has the admin role.
     *
     * @return bool
     */
    public function isAdmin()
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * {@inheritdoc}
     */
    public function isNew(): bool
    {
        return empty($this->id);
    }

    /**
     * Gets a value indicating if this righs overwrite the default rights.
     *
     * @return bool true if overwrite, false to use the default rights
     *
     * @see User::getRights()
     */
    public function isOverwrite(): bool
    {
        return $this->overwrite;
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
            $this->updatedAt = new \DateTimeImmutable();
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
     * Sets a value indicating if this righs overwrite the default rights.
     *
     * @param bool $overwrite true if overwrite, false to use the default rights
     */
    public function setOverwrite(bool $overwrite): self
    {
        $this->overwrite = $overwrite;

        return $this;
    }

    /**
     * Sets this role. This function is used to set a single role.
     *
     * @param string $role
     */
    public function setRole(?string $role): self
    {
        $role = $role ?: self::ROLE_DEFAULT;

        return $this->setRoles([$role]);
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
