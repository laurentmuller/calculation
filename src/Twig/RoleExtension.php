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

namespace App\Twig;

use App\Traits\RoleTranslatorTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for roles.
 */
class RoleExtension extends AbstractExtension
{
    use RoleTranslatorTrait;

    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_role', $this->translateRole(...)),
            new TwigFilter('role_icon', $this->getRoleIcon(...)),
        ];
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }
}
