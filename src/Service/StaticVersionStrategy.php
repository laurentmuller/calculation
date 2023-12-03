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

namespace App\Service;

use Symfony\Component\Asset\VersionStrategy\StaticVersionStrategy as BaseVersionStrategy;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Use the modification time of the composer lock file as version.
 */
class StaticVersionStrategy extends BaseVersionStrategy
{
    public function __construct(#[Autowire('%kernel.project_dir%/composer.lock')] string $file)
    {
        parent::__construct((string) \filemtime($file));
    }
}
