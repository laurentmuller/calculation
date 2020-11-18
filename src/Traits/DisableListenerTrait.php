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

namespace App\Traits;

/**
 * Trait for class implementing the <code>DisableListenerInterface</code> interface.
 *
 * @author Laurent Muller
 *
 * @see DisableListenerInterface
 */
trait DisableListenerTrait
{
    /**
     * The enabled state.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * {@inheritdoc}
     */
    public function setEnabled($enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }
}
