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
