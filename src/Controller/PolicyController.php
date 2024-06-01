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

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to handle the license agreement.
 */
#[AsController]
#[Route(path: '/policy', name: 'policy_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class PolicyController extends AbstractController
{
    use CookieTrait;

    final public const POLICY_ACCEPTED = 'POLICY_ACCEPTED';

    /**
     * Accept the license agreement.
     */
    #[Get(path: '/accept', name: 'accept')]
    public function accept(): RedirectResponse
    {
        $path = $this->getCookiePath();
        $response = $this->redirectToHomePage('cookie_banner.success');
        $this->updateCookie($response, self::POLICY_ACCEPTED, true, path: $path);

        return $response;
    }
}
