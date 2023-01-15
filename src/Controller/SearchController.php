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
use App\Table\SearchTable;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the search page.
 */
#[AsController]
#[IsGranted(RoleInterface::ROLE_USER)]
class SearchController extends AbstractController
{
    use TableTrait;

    /**
     * Render the table view.
     *
     * @throws \ReflectionException
     */
    #[Route(path: '/search', name: 'search')]
    public function search(Request $request, SearchTable $table, LoggerInterface $logger): Response
    {
        return $this->handleTableRequest($request, $table, 'search/search.html.twig', $logger);
    }
}
