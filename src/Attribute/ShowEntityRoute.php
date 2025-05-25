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

namespace App\Attribute;

use App\Controller\AbstractController;

/**
 * Shortcut route attribute to show an entity.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class ShowEntityRoute extends GetRoute
{
    public function __construct()
    {
        parent::__construct('/show/{id}', 'show', AbstractController::ID_REQUIREMENT);
    }
}
