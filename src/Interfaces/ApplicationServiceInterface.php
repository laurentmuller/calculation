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

namespace App\Interfaces;

use App\Enums\EntityAction;
use App\Enums\TableView;

/**
 * Application service constants and default values.
 *
 * @see \App\Service\ApplicationService
 */
interface ApplicationServiceInterface
{
    /**
     * The default action to trigger.
     */
    public const DEFAULT_ACTION = EntityAction::EDIT;

    /**
     * The default display mode (string).
     */
    public const DEFAULT_DISPLAY_MODE = TableView::TABLE;

    /**
     * The default display flash bag message close button (boolean).
     */
    public const DEFAULT_MESSAGE_CLOSE = true;

    /**
     * The default display flash bag message icon (boolean).
     */
    public const DEFAULT_MESSAGE_ICON = true;

    /**
     * The default position of the flash bag messages (string).
     */
    public const DEFAULT_MESSAGE_POSITION = 'bottom-right';

    /**
     * The default display message progress bar (boolean).
     */
    public const DEFAULT_MESSAGE_PROGRESS = true;

    /**
     * The default display flash bag message sub-title (boolean).
     */
    public const DEFAULT_MESSAGE_SUB_TITLE = false;

    /**
     * The default timeout of the flash bag messages (integer).
     */
    public const DEFAULT_MESSAGE_TIMEOUT = 4000;

    /**
     * The default display flash bag message title (boolean).
     */
    public const DEFAULT_MESSAGE_TITLE = true;

    /**
     * The default minimum margin of a calculation (float).
     */
    public const DEFAULT_MIN_MARGIN = 1.1;

    /**
     * The default number of displayed calculation in the home page (int).
     */
    public const DEFAULT_PANEL_CALCULATION = 10;

    /**
     * The default output customer address in PDF documents (boolean).
     */
    public const DEFAULT_PRINT_ADDRESS = false;

    /**
     * The default product edit (bool).
     */
    public const DEFAULT_PRODUCT_EDIT = true;

    /**
     * The default output qr-code (boolean).
     */
    public const DEFAULT_QR_CODE = false;

    /**
     * The property name for the administrator role rights (string).
     */
    public const P_ADMIN_RIGHTS = 'admin_rights';

    /**
     * The property name for the customer address (string).
     */
    public const P_CUSTOMER_ADDRESS = 'customer_address';

    /**
     * The property name for the customer email (string).
     */
    public const P_CUSTOMER_EMAIL = 'customer_email';

    /**
     * The property name for the customer fax number (string).
     */
    public const P_CUSTOMER_FAX = 'customer_fax';

    /**
     * The property name for the customer name (string).
     */
    public const P_CUSTOMER_NAME = 'customer_name';

    /**
     * The property name for the customer phone number (string).
     */
    public const P_CUSTOMER_PHONE = 'customer_phone';

    /**
     * The property name for the customer website (string).
     */
    public const P_CUSTOMER_URL = 'customer_url';

    /**
     * The property name for the customer zip code and city (string).
     */
    public const P_CUSTOMER_ZIP_CITY = 'customer_zip_city';

    /**
     * The property name for the default category (integer).
     */
    public const P_DEFAULT_CATEGORY = 'default_category';

    /**
     * The property name for the default product (integer).
     */
    public const P_DEFAULT_PRODUCT = 'default_product';

    /**
     * The property name for the default product edit (bool).
     */
    public const P_DEFAULT_PRODUCT_EDIT = 'default_product_edit';

    /**
     * The property name for the default product quantity (float).
     */
    public const P_DEFAULT_PRODUCT_QUANTITY = 'default_product_quantity';

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
     * The property name for the display mode (string).
     * <p>
     * Possible values are:
     * <ul>
     * <li>'<code>table</code>': Show values within a table (default).</li>
     * <li>'<code>custom</code>': Show values as cards.</li>
     * <li>'<code>card</code>': Show detailed values.</li>
     * </ul>
     * </p>.
     */
    public const P_DISPLAY_MODE = 'display_mode';

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
     * The property name for displaying close button (default = true) of the flash bag messages (boolean).
     */
    public const P_MESSAGE_CLOSE = 'message_close';

    /**
     * The property name for displaying icon (default = true) of the flash bag messages (boolean).
     */
    public const P_MESSAGE_ICON = 'message_icon';

    /**
     * The property name for the position (default = 'bottom-right') of the flash bag messages (string).
     */
    public const P_MESSAGE_POSITION = 'message_position';

    /**
     * The property name for displaying progress bar (default = true) of the flash bag messages (boolean).
     */
    public const P_MESSAGE_PROGRESS = 'message_progress';

    /**
     * The property name for displaying subtitle (default = true) of the flash bag messages (boolean).
     */
    public const P_MESSAGE_SUB_TITLE = 'message_sub_title';

    /**
     * The property name for the timeout (default = 4000 ms) of the flash bag messages in milliseconds (int).
     */
    public const P_MESSAGE_TIMEOUT = 'message_timeout';

    /**
     * The property name for displaying title (default = true) of the flash bag messages (boolean).
     */
    public const P_MESSAGE_TITLE = 'message_title';

    /**
     * The property name for the minimum margin (default = 300%), in percent, for a calculation (float).
     */
    public const P_MIN_MARGIN = 'minimum_margin';

    /**
     * The property name for the minimum password strength (int).
     */
    public const P_MIN_STRENGTH = 'min_strength';

    /**
     * The property name for the number of displayed calculation in the home page (int).
     */
    public const P_PANEL_CALCULATION = 'panel_calculation';

    /**
     * The property name for displaying catalog panel in the home page (bool).
     */
    public const P_PANEL_CATALOG = 'panel_catalog';

    /**
     * The property name for displaying month panel in the home page (bool).
     */
    public const P_PANEL_MONTH = 'panel_month';

    /**
     * The property name for displaying state panel in the home page (bool).
     */
    public const P_PANEL_STATE = 'panel_state';

    /**
     * The property name to output the customer address in PDF documents.
     */
    public const P_PRINT_ADDRESS = 'print_address';

    /**
     * The property name to output a QR Code at the end of the PDF calculation document.
     */
    public const P_QR_CODE = 'qr_code';

    /**
     * The property name for the last products update (date).
     */
    public const P_UPDATE_PRODUCTS = 'update_products';

    /**
     * The property name for the user role rights (string).
     */
    public const P_USER_RIGHTS = 'user_rights';
}
