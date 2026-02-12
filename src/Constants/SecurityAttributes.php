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

namespace App\Constants;

/**
 * Contains security constants.
 */
final class SecurityAttributes
{
    /** The authentication token name. */
    public const string AUTHENTICATE_TOKEN = 'authenticate';

    /** The captcha field name. */
    public const string CAPTCHA_FIELD = 'captcha';

    /** The allowed content type format. */
    public const string CONTENT_TYPE_FORMAT = 'form';

    /** The development firewall name. */
    public const string DEV_FIREWALL = 'dev';

    /** The login route name. */
    public const string LOGIN_ROUTE = 'app_login';

    /** The login token name. */
    public const string LOGIN_TOKEN = 'login_token';

    /** The logout route name. */
    public const string LOGOUT_ROUTE = 'app_logout';

    /** The logout success route name. */
    public const string LOGOUT_SUCCESS_ROUTE = 'app_logout_success';

    /** The logout token name. */
    public const string LOGOUT_TOKEN = 'logout_token';

    /** The main firewall name. */
    public const string MAIN_FIREWALL = 'main';

    /** The password field name. */
    public const string PASSWORD_FIELD = 'password';

    /** The remember field name. */
    public const string REMEMBER_FIELD = 'remember';

    /** The user field name. */
    public const string USER_FIELD = 'username';
}
