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

namespace App\Pdf;

/**
 * Class implementing this interface deals with group render.
 *
 * @author Laurent Muller
 */
interface PdfGroupListenerInterface
{
    /**
     * Called when a group must be rendered.
     *
     * @param PdfGroupTableBuilder $parent the parent table
     * @param PdfGroup             $group  the group to output
     *
     * @return bool true if the listener handes the output; false to use the default output
     */
    public function onOutputGroup(PdfGroupTableBuilder $parent, PdfGroup $group): bool;
}
