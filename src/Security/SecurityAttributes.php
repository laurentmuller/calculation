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

namespace App\Security;

/**
 * Contains security constants.
 */
final class SecurityAttributes
{
    /**
     * The authentication token name.
     */
    public const AUTHENTICATE_TOKEN = 'authenticate';

    /**
     * The captcha field name.
     */
    public const CAPTCHA_FIELD = 'captcha';

    /**
     * The allowed content type format.
     */
    public const CONTENT_TYPE_FORMAT = 'form';

    /**
     * The login route name.
     */
    public const LOGIN_ROUTE = 'app_login';

    /**
     * The login token name.
     */
    public const LOGIN_TOKEN = 'login_token';

    /**
     * The logout route name.
     */
    public const LOGOUT_ROUTE = 'app_logout';

    /**
     * The logout success route name.
     */
    public const LOGOUT_SUCCESS_ROUTE = 'app_logout_success';

    /**
     * The logout token name.
     */
    public const LOGOUT_TOKEN = 'logout_token';

    /**
     * The password field name.
     */
    public const PASSWORD_FIELD = 'password';

    /**
     * The remember field name.
     */
    public const REMEMBER_FIELD = 'remember';

    /**
     * The user field name.
     */
    public const USER_FIELD = 'username';
}
