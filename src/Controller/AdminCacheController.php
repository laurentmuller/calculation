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

use App\Attribute\ForAdmin;
use App\Attribute\GetPostRoute;
use App\Enums\FlashType;
use App\Model\TranslatableFlashMessage;
use App\Service\CacheService;
use App\Traits\FormExceptionTrait;
use App\Utils\FileUtils;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to clear the application cache.
 */
#[ForAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminCacheController extends AbstractController
{
    use FormExceptionTrait;

    #[GetPostRoute(path: '/clear', name: 'clear')]
    public function clearCache(
        Request $request,
        KernelInterface $kernel,
        CacheService $service,
        LoggerInterface $logger
    ): Response {
        $form = $this->createForm(FormType::class)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                if ($service->clear()) {
                    return $this->redirectToHomePage(
                        request: $request,
                        message: 'clear_cache.success'
                    );
                }

                return $this->redirectToHomePage(
                    request: $request,
                    message: TranslatableFlashMessage::instance(
                        message: 'clear_cache.failure',
                        type: FlashType::DANGER
                    )
                );
            } catch (\Exception $e) {
                return $this->renderFormException('clear_cache.failure', $e, $logger);
            }
        }

        try {
            $pools = $service->list();
        } catch (\Exception) {
            $pools = [];
        }

        return $this->render('admin/clear_cache.html.twig', [
            'size' => FileUtils::formatSize($kernel->getCacheDir()),
            'pools' => $pools,
            'form' => $form,
        ]);
    }
}
