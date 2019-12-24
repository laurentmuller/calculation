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

namespace App\Form\Type;

/**
 * Abstract choice type for separator.
 *
 * @author Laurent Muller
 */
abstract class SeparatorType extends AbstractChoiceType
{
    /**
     * The comma character separator.
     */
    const COMMA_CHAR = ',';

    /**
     * The period character separator.
     */
    const PERIOD_CHAR = '.';

    /**
     * The quote character separator.
     */
    const QUOTE_CHAR = "'";

    /**
     * The space character separator.
     */
    const SPACE_CHAR = ' ';
}
