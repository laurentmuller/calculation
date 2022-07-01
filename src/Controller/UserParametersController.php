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

use App\Form\User\UserParametersType;
use App\Service\UserService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display user's preferences.
 */
#[AsController]
#[IsGranted('ROLE_USER')]
#[Route(path: '/user')]
class UserParametersController extends AbstractController
{
    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/parameters', name: 'user_parameters')]
    public function invoke(Request $request, UserService $service): Response
    {
        // form
        $form = $this->createForm(UserParametersType::class, $service->getProperties());
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array<string, mixed> $data */
            $data = $form->getData();
            $service->setProperties($data);
            $this->successTrans('user.parameters.success');

            return $this->redirectToHomePage();
        }

        // display
        return $this->renderForm('user/user_parameters.html.twig', [
            'form' => $form,
        ]);
    }
}
