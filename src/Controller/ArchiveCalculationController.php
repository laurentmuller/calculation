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

use App\Interfaces\PropertyServiceInterface;
use App\Interfaces\RoleInterface;
use App\Service\ArchiveService;
use App\Service\SuspendEventListenerService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to archive calculations.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class ArchiveCalculationController extends AbstractController
{
    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/archive', name: 'admin_archive')]
    public function invoke(Request $request, ArchiveService $service, SuspendEventListenerService $listener): Response
    {
        $application = $this->getApplication();
        $query = $service->createQuery();
        $form = $service->createForm($query);

        // handle request
        if ($this->handleRequestForm($request, $form)) {
            try {
                // save
                $service->saveQuery($query);

                // update
                $listener->disableListeners();
                $result = $service->processQuery($query);

                // update last date
                if (!$query->isSimulate() && $result->isValid()) {
                    $application->setProperty(PropertyServiceInterface::P_DATE_CALCULATION, new \DateTime());
                }

                return $this->renderForm('admin/archive_result.html.twig', [
                    'result' => $result,
                ]);
            } finally {
                $listener->enableListeners();
            }
        }

        return $this->renderForm('admin/archive_query.html.twig', [
            'last_update' => $application->getArchiveCalculation(),
            'form' => $form,
        ]);
    }
}
