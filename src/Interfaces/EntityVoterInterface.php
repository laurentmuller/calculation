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
 * Entity voter constants.
 *
 * @author Laurent Muller
 */
interface EntityVoterInterface
{
    /**
     * The add attribute name.
     */
    public const ATTRIBUTE_ADD = 'add';

    /**
     * The delete attribute name.
     */
    public const ATTRIBUTE_DELETE = 'delete';

    /**
     * The edit attribute name.
     */
    public const ATTRIBUTE_EDIT = 'edit';

    /**
     * The export attribute name.
     */
    public const ATTRIBUTE_EXPORT = 'export';

    /**
     * The list attribute name.
     */
    public const ATTRIBUTE_LIST = 'list';

    /**
     * The show attribute name.
     */
    public const ATTRIBUTE_SHOW = 'show';

    /**
     * The entity prefix.
     */
    public const ENTITY = 'Entity';

    /**
     * The calculation rights.
     */
    public const ENTITY_CALCULATION = 'EntityCalculation';

    /**
     * The calculation state rights.
     */
    public const ENTITY_CALCULATION_STATE = 'EntityCalculationState';

    /**
     * The category rights.
     */
    public const ENTITY_CATEGORY = 'EntityCategory';

    /**
     * The customer rights.
     */
    public const ENTITY_CUSTOMER = 'EntityCustomer';

    /**
     * The global margin rights.
     */
    public const ENTITY_GLOBAL_MARGIN = 'EntityGlobalMargin';

    /**
     * The group rights.
     */
    public const ENTITY_GROUP = 'EntityGroup';

    /**
     * The log rights.
     */
    public const ENTITY_LOG = 'EntityLog';

    /**
     * The product rights.
     */
    public const ENTITY_PRODUCT = 'EntityProduct';

    /**
     * The user rights.
     */
    public const ENTITY_USER = 'EntityUser';
}
