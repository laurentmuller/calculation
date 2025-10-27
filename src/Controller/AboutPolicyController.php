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

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to output policy information.
 */
#[Route(path: '/about/policy', name: 'about_policy_')]
class AboutPolicyController extends AbstractAboutController
{
    /**
     * The policy file name (markdown).
     */
    public const POLICY_FILE = 'POLICY.md';

    #[\Override]
    protected function getFileName(): string
    {
        return self::POLICY_FILE;
    }

    #[\Override]
    protected function getTags(): array
    {
        return [
            ['h4', 'h6', 'bookmark bookmark-2'],
            ['h3', 'h5', 'bookmark bookmark-1'],
            ['h2', 'h4', 'bookmark'],
        ];
    }

    #[\Override]
    protected function getTitle(): string
    {
        return 'about.policy.title';
    }

    #[\Override]
    protected function getView(): string
    {
        return 'about/policy.html.twig';
    }
}
