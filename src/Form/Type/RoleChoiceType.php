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

namespace App\Form\Type;

use App\Entity\User;

/**
 * A single role choice type.
 *
 * @author Laurent Muller
 */
class RoleChoiceType extends AbstractChoiceType
{
    /**
     * {@inheritdoc}
     */
    protected function getChoices(): array
    {
        return [
            'user.roles.user' => User::ROLE_USER,
            'user.roles.admin' => User::ROLE_ADMIN,
            'user.roles.super_admin' => User::ROLE_SUPER_ADMIN,
        ];
    }
}
