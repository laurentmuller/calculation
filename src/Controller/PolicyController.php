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

use App\Attribute\ForUser;
use App\Attribute\GetRoute;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to handle the license agreement.
 */
#[ForUser]
#[Route(path: '/policy', name: 'policy_')]
class PolicyController extends AbstractController
{
    use CookieTrait;

    final public const POLICY_ACCEPTED = 'POLICY_ACCEPTED';

    /**
     * Accept the license agreement.
     */
    #[GetRoute(path: '/accept', name: 'accept')]
    public function accept(): RedirectResponse
    {
        $response = $this->redirectToHomePage('cookie_banner.success');
        $this->updateCookie($response, self::POLICY_ACCEPTED, true);

        return $response;
    }
}
