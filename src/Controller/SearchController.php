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

use App\Table\SearchTable;
use App\Traits\TableTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the search page.
 */
class SearchController extends AbstractController
{
    use TableTrait;

    /**
     * Render the table view.
     */
    #[IsGranted('ROLE_USER')]
    #[Route(path: '/search', name: 'search')]
    public function search(Request $request, SearchTable $table): Response
    {
        return $this->handleTableRequest($request, $table, 'index/search.html.twig');
    }
}
