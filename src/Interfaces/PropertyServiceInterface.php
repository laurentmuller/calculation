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
use App\Enums\MessagePosition;
use App\Enums\StrengthLevel;
use App\Enums\TableView;
use App\Model\CustomerInformation;

/**
 * Interface for application and user properties.
 *
 * @see \App\Service\ApplicationService
 * @see \App\Service\UserService
 */
interface PropertyServiceInterface
{
    /**
     * The default action to trigger.
     */
    final public const DEFAULT_ACTION = EntityAction::EDIT;

    /**
     * The default display mode (string).
     */
    final public const DEFAULT_DISPLAY_MODE = TableView::TABLE;

    /**
     * The default display flash bag message close button (boolean).
     */
    final public const DEFAULT_MESSAGE_CLOSE = true;

    /**
     * The default display flash bag message icon (boolean).
     */
    final public const DEFAULT_MESSAGE_ICON = true;

    /**
     * The default position of the flash bag messages (string).
     */
    final public const DEFAULT_MESSAGE_POSITION = MessagePosition::BOTTOM_RIGHT;

    /**
     * The default display message progress bar height (int).
     */
    final public const DEFAULT_MESSAGE_PROGRESS = 1;

    /**
     * The default display flash bag message sub-title (boolean).
     */
    final public const DEFAULT_MESSAGE_SUB_TITLE = false;

    /**
     * The default timeout of the flash bag messages (integer).
     */
    final public const DEFAULT_MESSAGE_TIMEOUT = 4000;

    /**
     * The default display flash bag message title (boolean).
     */
    final public const DEFAULT_MESSAGE_TITLE = true;

    /**
     * The default minimum margin of a calculation (float).
     */
    final public const DEFAULT_MIN_MARGIN = 1.1;

    /**
     * The default number of displayed calculation in the home page (int).
     */
    final public const DEFAULT_PANEL_CALCULATION = 10;

    /**
     * The default output customer address in PDF documents (boolean).
     */
    final public const DEFAULT_PRINT_ADDRESS = false;

    /**
     * The default product edit (bool).
     */
    final public const DEFAULT_PRODUCT_EDIT = true;

    /**
     * The default output qr-code (boolean).
     */
    final public const DEFAULT_QR_CODE = false;

    /**
     * The default password strength level.
     */
    final public const DEFAULT_STRENGTH_LEVEL = StrengthLevel::NONE;

    /**
     * The property name for the administrator role rights (string).
     */
    final public const P_ADMIN_RIGHTS = 'admin_rights';

    /**
     * The property name for the customer address (string).
     */
    final public const P_CUSTOMER_ADDRESS = 'customer_address';

    /**
     * The property name for the customer email (string).
     */
    final public const P_CUSTOMER_EMAIL = 'customer_email';

    /**
     * The property name for the customer fax number (string).
     */
    final public const P_CUSTOMER_FAX = 'customer_fax';

    /**
     * The property name for the customer name (string).
     */
    final public const P_CUSTOMER_NAME = 'customer_name';

    /**
     * The property name for the customer phone number (string).
     */
    final public const P_CUSTOMER_PHONE = 'customer_phone';

    /**
     * The property name for the customer website (string).
     */
    final public const P_CUSTOMER_URL = 'customer_url';

    /**
     * The property name for the customer zip code and city (string).
     */
    final public const P_CUSTOMER_ZIP_CITY = 'customer_zip_city';

    /**
     * The property name for the date of the last archiving calculations (date).
     */
    final public const P_DATE_CALCULATION = 'archive_calculation';

    /**
     * The property name for the last date of import of Swiss cities (date).
     */
    final public const P_DATE_IMPORT = 'last_import';

    /**
     * The property name for the date of the last updating prices of products (date).
     */
    final public const P_DATE_PRODUCT = 'update_products';

    /**
     * The property name for the default category (integer).
     */
    final public const P_DEFAULT_CATEGORY = 'default_category';

    /**
     * The property name for the default product (integer).
     */
    final public const P_DEFAULT_PRODUCT = 'default_product';

    /**
     * The property name for the default product edit (bool).
     */
    final public const P_DEFAULT_PRODUCT_EDIT = 'default_product_edit';

    /**
     * The property name for the default product quantity (float).
     */
    final public const P_DEFAULT_PRODUCT_QUANTITY = 'default_product_quantity';

    /**
     * The property name for the default calculation state (integer).
     */
    final public const P_DEFAULT_STATE = 'default_state';

    /**
     * The property name to show or hide image captcha when login (boolean).
     *
     * When <code>true</code>, display the image; when <code>false</code>, hide.
     */
    final public const P_DISPLAY_CAPTCHA = 'display_captcha';

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
    final public const P_DISPLAY_MODE = 'display_mode';

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
    final public const P_EDIT_ACTION = 'edit_action';

    /**
     * The property name for displaying close button (default = true) of the flash bag messages (boolean).
     */
    final public const P_MESSAGE_CLOSE = 'message_close';

    /**
     * The property name for displaying icon (default = true) of the flash bag messages (boolean).
     */
    final public const P_MESSAGE_ICON = 'message_icon';

    /**
     * The property name for the position (default = 'bottom-right') of the flash bag messages (string).
     */
    final public const P_MESSAGE_POSITION = 'message_position';

    /**
     * The property name for progress bar height (default = 1 px) of the flash bag messages (integer).
     */
    final public const P_MESSAGE_PROGRESS = 'message_progress';

    /**
     * The property name for displaying subtitle (default = true) of the flash bag messages (boolean).
     */
    final public const P_MESSAGE_SUB_TITLE = 'message_sub_title';

    /**
     * The property name for the timeout (default = 4000 ms) of the flash bag messages in milliseconds (int).
     */
    final public const P_MESSAGE_TIMEOUT = 'message_timeout';

    /**
     * The property name for displaying title (default = true) of the flash bag messages (boolean).
     */
    final public const P_MESSAGE_TITLE = 'message_title';

    /**
     * The property name for the minimum margin (default = 300%), in percent, for a calculation (float).
     */
    final public const P_MIN_MARGIN = 'minimum_margin';

    /**
     * The property name for the minimum password strength level (int).
     */
    final public const P_MIN_STRENGTH = 'min_strength';

    /**
     * The property name for the number of displayed calculation in the home page (int).
     */
    final public const P_PANEL_CALCULATION = 'panel_calculation';

    /**
     * The property name for displaying catalog panel in the home page (bool).
     */
    final public const P_PANEL_CATALOG = 'panel_catalog';

    /**
     * The property name for displaying month panel in the home page (bool).
     */
    final public const P_PANEL_MONTH = 'panel_month';

    /**
     * The property name for displaying state panel in the home page (bool).
     */
    final public const P_PANEL_STATE = 'panel_state';

    /**
     * The property name to output the customer address in PDF documents.
     */
    final public const P_PRINT_ADDRESS = 'print_address';

    /**
     * The property name to output a QR Code at the end of the PDF calculation document.
     */
    final public const P_QR_CODE = 'qr_code';

    /**
     * The property name for the password strength level (int).
     */
    final public const P_STATUS_BAR = 'status_bar';

    /**
     * The property name for the password strength level (int).
     */
    final public const P_STRENGTH_LEVEL = 'strength_level';

    /**
     * The property name for the user role rights (string).
     */
    public const P_USER_RIGHTS = 'user_rights';

    /**
     * The password options.
     */
    final public const PASSWORD_OPTIONS = [
        'letters',
        'numbers',
        'special_char',
        'case_diff',
        'email',
        'pwned',
    ];

    /**
     * Gets the customer information.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function getCustomer(): CustomerInformation;

    /**
     * Gets the display mode for table.
     */
    public function getDisplayMode(): TableView;

    /**
     * Gets the action to trigger within the entities.
     */
    public function getEditAction(): EntityAction;

    /**
     * Gets the position of the flash bag messages (default: 'bottom-right').
     */
    public function getMessagePosition(): MessagePosition;

    /**
     * Gets the message progress bar height (default: 1 pixel).
     */
    public function getMessageProgress(): int;

    /**
     * Gets the timeout, in milliseconds, of the flash bag messages (default: 4000 ms).
     */
    public function getMessageTimeout(): int;

    /**
     * Returns a value indicating number of displayed calculation in the home page.
     */
    public function getPanelCalculation(): int;

    /**
     * Returns a value indicating if the default action is to edit the entity.
     */
    public function isActionEdit(): bool;

    /**
     * Returns a value indicating if the default action is to do nothing.
     */
    public function isActionNone(): bool;

    /**
     * Returns a value indicating if the default action is to show the entity.
     */
    public function isActionShow(): bool;

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     */
    public function isMessageClose(): bool;

    /**
     * Returns if the flash bag message icon is displayed (default: true).
     */
    public function isMessageIcon(): bool;

    /**
     * Returns if the flash bag message subtitle is displayed (default: true).
     */
    public function isMessageSubTitle(): bool;

    /**
     * Returns if the flash bag message title is displayed (default: true).
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isMessageTitle(): bool;

    /**
     * Returns a value indicating if the catalog panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelCatalog(): bool;

    /**
     * Returns a value indicating if the month panel is displayed in the home page.
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function isPanelMonth(): bool;

    /**
     * Returns a value indicating if the state panel is displayed in the home page.
     */
    public function isPanelState(): bool;

    /**
     * Gets a value indicating if the customer address is output within the PDF documents.
     */
    public function isPrintAddress(): bool;

    /**
     * Gets a value indicating if a QR-Code is output at the end of the PDF documents.
     */
    public function isQrCode(): bool;

    /**
     * Returns a value indicating if the status bar is displayed.
     */
    public function isStatusBar(): bool;
}
