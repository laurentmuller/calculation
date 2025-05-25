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
use Symfony\Component\Routing\Attribute\Route;

/**
 * Shortcut route attribute to edit an entity.
 */
#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD)]
class EditEntityRoute extends GetPostRoute
{
    /**
     * This route name.
     */
    final public const NAME = 'edit';

    public function __construct()
    {
        parent::__construct('/edit/{id}', self::NAME, AbstractController::ID_REQUIREMENT);
    }
}
