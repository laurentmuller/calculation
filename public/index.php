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

use App\Kernel;

require_once \dirname(__DIR__) . '/vendor/autoload_runtime.php';

return fn (array $context) => new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
