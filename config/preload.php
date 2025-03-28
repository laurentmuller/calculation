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

$file = \dirname(__DIR__) . '/var/cache/prod/App_KernelProdContainer.preload.php';
if (\file_exists($file)) {
    require $file;
}
