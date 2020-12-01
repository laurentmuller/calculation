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

namespace App\Interfaces;

/**
 * Application service constants.
 *
 * @author Laurent Muller
 *
 * @see \App\Service\ApplicationService
 */
interface ApplicationServiceInterface
{
    /**
     * The property name for the administrator role rights (string).
     */
    public const ADMIN_RIGHTS = 'admin_rights';

    /**
     * The property name for the customer name (string).
     */
    public const CUSTOMER_NAME = 'customer_name';

    /**
     * The property name for the customer web site (string).
     */
    public const CUSTOMER_URL = 'customer_url';

    /**
     * The default edit action (boolean).
     */
    public const DEFAULT_EDIT_ACTION = true;

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
     * The property name for the default calculation state (integer).
     */
    public const DEFAULT_STATE = 'default_state';

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
     * The property name to show or hide image captcha when login (boolean).
     *
     * When <code>true</code>, display the image; when <code>false</code>, hide.
     */
    public const DISPLAY_CAPTCHA = 'display_captcha';

    /**
     * The property name for the display mode (boolean).
     *
     * When <code>true</code>, displays the entities in tabular mode (default); when <code>false</code>, displays entities as cards.
     */
    public const DISPLAY_TABULAR = 'display_tabular';

    /**
     * The property name for the edit action when displaying entities (boolean).
     *
     * When <code>false</code>, display the entity properties; when <code>true</code> (default), edit the entity.
     */
    public const EDIT_ACTION = 'edit_action';

    /**
     * The property name for the last import of Swiss cities (date).
     */
    public const LAST_IMPORT = 'last_import';

    /**
     * The property name for the last calculations update (date).
     */
    public const LAST_UPDATE = 'last_update';

    /**
     * The property name for the number items displayed in the tables (integer).
     */
    public const LIST_LENGTH = 'list-length';

    /**
     * The property name for the position (default = 'bottom-right') of the flashbag messages (string).
     */
    public const MESSAGE_POSITION = 'message_position';

    /**
     * The property name for displaying sub-title (default = true) of the flashbag messages (boolean).
     */
    public const MESSAGE_SUB_TITLE = 'message_sub_title';

    /**
     * The property name for the timeout (default = 4000 ms) of the flashbag messages in milliseconds (int).
     */
    public const MESSAGE_TIMEOUT = 'message_timeout';

    /**
     * The property name for the minimum margin (default = 300%), in percent, for a calculation (float).
     */
    public const MIN_MARGIN = 'minimum_margin';

    /**
     * The property name for the minimum password strength (int).
     */
    public const MIN_STRENGTH = 'minstrength';

    /**
     * The property name for the user role rights (string).
     */
    public const USER_RIGHTS = 'user_rights';
}
