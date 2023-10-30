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

namespace App\Tests\EntityTrait;

use App\Entity\Group;
use App\Entity\GroupMargin;

/**
 * Trait to manage a group.
 */
trait GroupTrait
{
    private ?Group $group = null;

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    public function getGroup(string $code = 'Test Group'): Group
    {
        if (!$this->group instanceof Group) {
            $this->group = new Group();
            $this->group->setCode($code);
            $margin = new GroupMargin();
            $margin->setMinimum(0)
                ->setMaximum(1_000_000)
                ->setMargin(1.1);
            $this->group->addMargin($margin);
            $this->addEntity($this->group);
        }

        return $this->group; // @phpstan-ignore-line
    }

    /**
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function deleteGroup(): void
    {
        if ($this->group instanceof Group) {
            $this->group = $this->deleteEntity($this->group);
        }
    }
}
