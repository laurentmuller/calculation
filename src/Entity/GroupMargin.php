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

use App\Interfaces\ParentTimestampableInterface;
use App\Repository\GroupMarginRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Represents a margin within a group.
 *
 * @implements ParentTimestampableInterface<Group>
 */
#[ORM\Table(name: 'sy_GroupMargin')]
#[ORM\Entity(repositoryClass: GroupMarginRepository::class)]
class GroupMargin extends AbstractMargin implements ParentTimestampableInterface
{
    /**
     * The parent's group.
     */
    #[Assert\NotNull]
    #[ORM\ManyToOne(inversedBy: 'margins')]
    #[ORM\JoinColumn(name: 'group_id', nullable: false, onDelete: 'cascade')]
    private ?Group $group = null;

    public function getParentEntity(): ?Group
    {
        return $this->group;
    }

    /**
     * Set the group.
     */
    public function setGroup(?Group $group): self
    {
        $this->group = $group;

        return $this;
    }
}
