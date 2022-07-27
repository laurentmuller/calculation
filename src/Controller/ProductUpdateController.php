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
use App\Service\ProductUpdater;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to update product prices.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted('ROLE_ADMIN')]
class ProductUpdateController extends AbstractController
{
    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/product', name: 'admin_product')]
    public function invoke(Request $request, ProductUpdater $updater): Response
    {
        // create form
        $application = $this->getApplication();
        $query = $updater->createUpdateQuery();
        $form = $updater->createForm($query);

        // handle request
        if ($this->handleRequestForm($request, $form)) {
            // save query
            $updater->saveUpdateQuery($query);

            // update
            $result = $updater->update($query);

            // update last date
            if (!$query->isSimulate() && $result->isValid()) {
                $application->setProperty(PropertyServiceInterface::P_UPDATE_PRODUCTS, new \DateTime());
            }

            return $this->renderForm('admin/product_result.html.twig', [
                'result' => $result,
                'query' => $query,
            ]);
        }

        return $this->renderForm('admin/product_update.html.twig', [
            'last_update' => $application->getUpdateProducts(),
            'form' => $form,
        ]);
    }
}
