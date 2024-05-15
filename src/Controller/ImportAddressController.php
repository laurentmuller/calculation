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

use App\Attribute\GetPost;
use App\Interfaces\RoleInterface;
use App\Service\SwissPostUpdater;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to import Switzerland address.
 */
#[AsController]
#[Route(path: '/admin', name: 'admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class ImportAddressController extends AbstractController
{
    /**
     * The URL to download data.
     */
    private const DATA_URL = 'https://www.post.ch/fr/espace-clients/services-en-ligne/zopa/adress-und-geodaten/info';

    #[GetPost(path: '/import', name: '_import')]
    public function invoke(Request $request, SwissPostUpdater $updater): Response
    {
        $form = $updater->createForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @psalm-var array{file: UploadedFile|string|null, overwrite: bool} $data */
            $data = $form->getData();
            $file = $data['file'];
            $overwrite = $data['overwrite'];
            $result = $updater->import($file, $overwrite);

            return $this->render('admin/import_result.html.twig', [
                'result' => $result,
            ]);
        }

        return $this->render('admin/import_query.html.twig', [
            'last_import' => $this->getApplicationService()->getLastImport(),
            'data_url' => self::DATA_URL,
            'form' => $form,
        ]);
    }
}
