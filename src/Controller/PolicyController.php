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

use App\Interfaces\RoleInterface;
use App\Traits\CookieTrait;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to handle the license agreement.
 */
#[Route(path: '/policy')]
#[IsGranted(RoleInterface::ROLE_USER)]
class PolicyController extends AbstractController
{
    use CookieTrait;

    final public const POLICY_ACCEPTED = 'POLICY_ACCEPTED';

    /**
     * Accept the license agreement.
     */
    #[Route(path: '/accept', name: 'policy_accept')]
    public function invoke(): RedirectResponse
    {
        $path = $this->getCookiePath();
        $response = $this->redirectToHomePage();
        $this->updateCookie($response, self::POLICY_ACCEPTED, 1, '', $path);
        $this->successTrans('cookie_banner.success');

        return $response;
    }
}
