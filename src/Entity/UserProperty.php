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

use App\Repository\UserPropertyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A user's property.
 */
#[ORM\Entity(repositoryClass: UserPropertyRepository::class)]
#[ORM\Table(name: 'sy_UserProperty')]
#[ORM\UniqueConstraint(name: 'unique_user_property_user_name', columns: ['user_id', 'name'])]
#[UniqueEntity(fields: ['name', 'user'], message: 'property.unique_name')]
class UserProperty extends AbstractProperty
{
    /**
     * The parent's user.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'properties')]
    #[ORM\JoinColumn(name: 'user_id', nullable: false, onDelete: 'cascade')]
    private ?User $user = null;

    /**
     * Gets the parent's user.
     */
    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    /**
     * Create a new instance for the given name and user.
     */
    public static function instance(string $name, UserInterface $user): self
    {
        return (new self($name))->setUser($user);
    }

    /**
     * Sets the parent's user.
     */
    public function setUser(?UserInterface $user): self
    {
        $this->user = $user instanceof User ? $user : null;

        return $this;
    }
}
