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
 * Entity voter interface.
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
