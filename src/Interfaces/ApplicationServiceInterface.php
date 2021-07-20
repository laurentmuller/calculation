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

namespace App\Interfaces;

/**
 * Application service constants and default values.
 *
 * @author Laurent Muller
 *
 * @see \App\Service\ApplicationService
 */
interface ApplicationServiceInterface
{
    /**
     * The default action to trigger (string).
     */
    public const DEFAULT_ACTION = ActionInterface::ACTION_EDIT;

    /**
     * The default number of items displayed in the tables (integer).
     */
    public const DEFAULT_LIST_LENGTH = 15;

    /**
     * The default minimum margin of a calculation (float).
     */
    public const DEFAULT_MIN_MARGIN = 3;

    /**
     * The default position of the flashbag messages (string).
     */
    public const DEFAULT_POSITION = 'bottom-right';

    /**
     * The default display message sub-title (boolean).
     */
    public const DEFAULT_SUB_TITLE = false;

    /**
     * The default display mode (boolean).
     */
    public const DEFAULT_TABULAR = true;

    /**
     * The default timeout of the flashbag messages (integer).
     */
    public const DEFAULT_TIMEOUT = 4000;

    /**
     * The property name for the administrator role rights (string).
     */
    public const P_ADMIN_RIGHTS = 'admin_rights';

    /**
     * The property name for the customer name (string).
     */
    public const P_CUSTOMER_NAME = 'customer_name';

    /**
     * The property name for the customer web site (string).
     */
    public const P_CUSTOMER_URL = 'customer_url';

    /**
     * The property name for the default category (integer).
     */
    public const P_DEFAULT_CATEGORY = 'default_category';

    /**
     * The property name for the default calculation state (integer).
     */
    public const P_DEFAULT_STATE = 'default_state';

    /**
     * The property name to show or hide image captcha when login (boolean).
     *
     * When <code>true</code>, display the image; when <code>false</code>, hide.
     */
    public const P_DISPLAY_CAPTCHA = 'display_captcha';

    /**
     * The property name for the display mode (boolean).
     *
     * When <code>true</code>, displays the entities in tabular mode (default); when <code>false</code>, displays entities as cards.
     */
    public const P_DISPLAY_TABULAR = 'display_tabular';

    /**
     * The property name for the action to trigger within the entities (string).
     *
     * <p>
     * Possible values are:
     * <ul>
     * <li>'<code>edit</code>': The entity is edited.</li>
     * <li>'<code>show</code>': The entity is show.</li>
     * <li>'<code>none</code>': No action is triggered.</li>
     * </ul>
     * </p>
     */
    public const P_EDIT_ACTION = 'edit_action';

    /**
     * The property name for the last import of Swiss cities (date).
     */
    public const P_LAST_IMPORT = 'last_import';

    /**
     * The property name for the number items displayed in the tables (integer).
     */
    public const P_LIST_LENGTH = 'list-length';

    /**
     * The property name for the position (default = 'bottom-right') of the flashbag messages (string).
     */
    public const P_MESSAGE_POSITION = 'message_position';

    /**
     * The property name for displaying sub-title (default = true) of the flashbag messages (boolean).
     */
    public const P_MESSAGE_SUB_TITLE = 'message_sub_title';

    /**
     * The property name for the timeout (default = 4000 ms) of the flashbag messages in milliseconds (int).
     */
    public const P_MESSAGE_TIMEOUT = 'message_timeout';

    /**
     * The property name for the minimum margin (default = 300%), in percent, for a calculation (float).
     */
    public const P_MIN_MARGIN = 'minimum_margin';

    /**
     * The property name for the minimum password strength (int).
     */
    public const P_MIN_STRENGTH = 'minstrength';

    /**
     * The property name for the last calculations update (date).
     */
    public const P_UPDATE_CALCULATIONS = 'update_calculations';

    /**
     * The property name for the last products update (date).
     */
    public const P_UPDATE_PRODUCTS = 'update_products';

    /**
     * The property name for the user role rights (string).
     */
    public const P_USER_RIGHTS = 'user_rights';
}
