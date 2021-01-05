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

use Doctrine\ORM\Mapping as ORM;

/**
 * Represents a margin within a group.
 *
 * @author Laurent Muller
 *
 * @ORM\Entity(repositoryClass="App\Repository\GroupMarginRepository")
 * @ORM\Table(name="sy_GroupMargin")
 */
class GroupMargin extends AbstractMargin
{
    /**
     * The parent's group.
     *
     * @ORM\ManyToOne(targetEntity=Group::class, inversedBy="margins")
     * @ORM\JoinColumn(name="group_id", referencedColumnName="id", nullable=false)
     *
     * @var Group
     */
    protected $group;

    /**
     * Get the group.
     */
    public function getGroup(): ?Group
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
