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

namespace App\Faker;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * User provider.
 *
 * @author Laurent Muller
 *
 * @template-extends EntityProvider<User>
 */
class UserProvider extends EntityProvider
{
    /**
     * Constructor.
     */
    public function __construct(Generator $generator, EntityManagerInterface $manager)
    {
        parent::__construct($generator, $manager, User::class);
    }

    /**
     * Gets a random user.
     */
    public function user(): ?User
    {
        return $this->entity();
    }

    /**
     * Gets a random user name.
     */
    public function userName(): ?string
    {
        return $this->user()->getUsername();
    }

    /**
     * Gets the number of users.
     */
    public function usersCount(): int
    {
        return $this->count();
    }

    /**
     * {@inheritDoc}
     */
    protected function findAll()
    {
        return $this->getRepository()->findBy(['enabled' => true]);
    }
}
