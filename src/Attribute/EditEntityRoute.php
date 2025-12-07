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

use Symfony\Component\Routing\Requirement\Requirement;

/**
 * Shortcut route attribute to edit an entity.
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class EditEntityRoute extends GetPostRoute
{
    public function __construct()
    {
        parent::__construct('/edit/{id}', 'edit', ['id' => Requirement::DIGITS]);
    }
}
