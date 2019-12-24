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

namespace App\Twig;

use App\Twig\TokenParser\SwitchTokenParser;
use Twig\Extension\AbstractExtension;

/**
 * Switch extension.
 *
 * @author Laurent Muller
 */
final class SwitchExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    public function getTokenParsers(): array
    {
        return [new SwitchTokenParser()];
    }
}
