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
     * The list attribute name.
     */
    public const ATTRIBUTE_LIST = 'list';

    /**
     * The Pdf attribute name.
     */
    public const ATTRIBUTE_PDF = 'pdf';

    /**
     * The show attribute name.
     */
    public const ATTRIBUTE_SHOW = 'show';

    /**
     * The calculation rights.
     */
    public const ENTITY_CALCULATION = 'Calculation';

    /**
     * The calculation state rights.
     */
    public const ENTITY_CALCULATION_STATE = 'CalculationState';

    /**
     * The category rights.
     */
    public const ENTITY_CATEGORY = 'Category';

    /**
     * The customer rights.
     */
    public const ENTITY_CUSTOMER = 'Customer';

    /**
     * The global margin rights.
     */
    public const ENTITY_GLOBAL_MARGIN = 'GlobalMargin';

    /**
     * The product rights.
     */
    public const ENTITY_PRODUCT = 'Product';

    /**
     * The user rights.
     */
    public const ENTITY_USER = 'User';
}
