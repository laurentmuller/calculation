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
use App\Service\SwissPostUpdater;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to import Switzerland address.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class ImportAddressController extends AbstractController
{
    /**
     * @throws \Psr\Cache\InvalidArgumentException
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '/import', name: 'admin_import')]
    public function invoke(Request $request, SwissPostUpdater $updater): Response
    {
        $form = $updater->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array{file: string|UploadedFile|null} $data */
            $data = $form->getData();
            $file = $data['file'];
            $results = $updater->import($file);

            return $this->renderForm('admin/import_result.html.twig', [
                'results' => $results,
            ]);
        }

        return $this->renderForm('admin/import_file.html.twig', [
            'last_import' => $this->getApplication()->getLastImport(),
            'form' => $form,
        ]);
    }
}
