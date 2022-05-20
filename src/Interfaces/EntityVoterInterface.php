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

/**
 * Entity voter constants.
 */
interface EntityVoterInterface
{
    /**
     * The add attribute name.
     */
    final public const ATTRIBUTE_ADD = 'add';

    /**
     * The delete attribute name.
     */
    final public const ATTRIBUTE_DELETE = 'delete';

    /**
     * The edit attribute name.
     */
    final public const ATTRIBUTE_EDIT = 'edit';

    /**
     * The export attribute name.
     */
    final public const ATTRIBUTE_EXPORT = 'export';

    /**
     * The list attribute name.
     */
    final public const ATTRIBUTE_LIST = 'list';

    /**
     * The show attribute name.
     */
    final public const ATTRIBUTE_SHOW = 'show';

    /**
     * The calculation rights.
     */
    final public const ENTITY_CALCULATION = 'EntityCalculation';

    /**
     * The calculation state rights.
     */
    final public const ENTITY_CALCULATION_STATE = 'EntityCalculationState';

    /**
     * The category rights.
     */
    final public const ENTITY_CATEGORY = 'EntityCategory';

    /**
     * The customer rights.
     */
    final public const ENTITY_CUSTOMER = 'EntityCustomer';

    /**
     * The global margin rights.
     */
    final public const ENTITY_GLOBAL_MARGIN = 'EntityGlobalMargin';

    /**
     * The group rights.
     */
    final public const ENTITY_GROUP = 'EntityGroup';

    /**
     * The log rights.
     */
    final public const ENTITY_LOG = 'EntityLog';

    /**
     * The product rights.
     */
    final public const ENTITY_PRODUCT = 'EntityProduct';

    /**
     * The task rights.
     */
    final public const ENTITY_TASK = 'EntityTask';

    /**
     * The user rights.
     */
    final public const ENTITY_USER = 'EntityUser';
}
