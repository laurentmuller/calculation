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

namespace App\Faker;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * User provider.
 *
 * @template-extends EntityProvider<User, UserRepository>
 */
class UserProvider extends EntityProvider
{
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
     * Gets a random username.
     */
    public function userName(): ?string
    {
        $user = $this->user();

        return ($user instanceof User) ? $user->getUserIdentifier() : null;
    }

    /**
     * Gets the number of users.
     *
     * @psalm-api
     */
    public function usersCount(): int
    {
        return $this->count();
    }

    protected function getCriteria(): array
    {
        return ['enabled' => true];
    }
}
