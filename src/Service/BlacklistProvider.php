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

namespace App\Service;

/**
 * Blacklist provider reading passwords from a text file.
 *
 * @author Laurent Muller
 */
class BlacklistProvider
{
    /**
     * The blacklist passwords.
     *
     * @var string[]
     */
    private $passwords;

    /**
     * Constructor.
     *
     * @param string $path the full path name
     */
    public function __construct(string $path)
    {
        if (\file_exists($path)) {
            $this->passwords = \file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            $this->passwords = [];
        }
    }

    /**
     * Returns whether the provided password is blacklisted.
     */
    public function isBlacklisted(string $password): bool
    {
        return \in_array(\strtolower($password), $this->passwords, true);
    }
}
