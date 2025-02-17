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

use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to output license information.
 */
#[AsController]
#[Route(path: '/about/licence', name: 'about_licence_')]
class AboutLicenceController extends AbstractAboutController
{
    /**
     * The license file name (markdown).
     */
    public const LICENCE_FILE = 'LICENSE.md';

    #[\Override]
    protected function getFileName(): string
    {
        return self::LICENCE_FILE;
    }

    #[\Override]
    protected function getTags(): array
    {
        return [
            ['h2', 'h4', 'bookmark'],
        ];
    }

    #[\Override]
    protected function getTitle(): string
    {
        return 'about.licence';
    }

    #[\Override]
    protected function getView(): string
    {
        return 'about/licence.html.twig';
    }
}
