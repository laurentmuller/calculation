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

namespace App\Form\User;

use App\Entity\User;
use App\Form\AbstractListEntityType;
use App\Repository\UserRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Type to display a list of users.
 *
 * @template-extends AbstractListEntityType<User>
 */
class UserListType extends AbstractListEntityType
{
    public function __construct()
    {
        parent::__construct(User::class);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'choice_label' => 'NameAndEmail',
            'query_builder' => static fn (UserRepository $repository): QueryBuilder => $repository->getSortedBuilder(),
        ]);
    }
}
