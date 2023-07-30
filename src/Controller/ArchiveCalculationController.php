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
use App\Service\CalculationArchiveService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to archive calculations.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class ArchiveCalculationController extends AbstractController
{
    #[Route(path: '/archive', name: 'admin_archive')]
    public function invoke(Request $request, CalculationArchiveService $service): Response
    {
        $application = $this->getApplication();
        $query = $service->createQuery();
        $form = $service->createForm($query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $application->setLastArchiveCalculations();
            }

            return $this->render('admin/archive_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/archive_query.html.twig', [
            'last_update' => $application->getLastArchiveCalculations(),
            'form' => $form,
        ]);
    }
}
