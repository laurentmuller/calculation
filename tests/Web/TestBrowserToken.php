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

namespace App\Tests\Web;

use Symfony\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * A very limited token that is used to login in tests using the KernelBrowser.
 *
 * @author Wouter de Jong <wouter@wouterj.nl>
 */
class TestBrowserToken extends AbstractToken
{
    public function __construct(array $roles = [], ?UserInterface $user = null)
    {
        parent::__construct($roles);

        if (null !== $user) {
            $this->setUser($user);
        }
    }

    public function getCredentials()
    {
        return null;
    }
}
