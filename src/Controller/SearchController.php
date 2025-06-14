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

use App\Attribute\GetRoute;
use App\Interfaces\RoleInterface;
use App\Resolver\DataQueryValueResolver;
use App\Table\DataQuery;
use App\Table\SearchTable;
use App\Traits\TableTrait;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to display the search page.
 */
#[IsGranted(RoleInterface::ROLE_USER)]
class SearchController extends AbstractController
{
    use TableTrait;

    /**
     * Render the table view.
     */
    #[GetRoute(path: '/search', name: 'search')]
    public function search(
        SearchTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'search/search.html.twig');
    }
}
