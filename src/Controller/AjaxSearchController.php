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
use App\Repository\AbstractRepository;
use App\Repository\CalculationRepository;
use App\Repository\CustomerRepository;
use App\Repository\ProductRepository;
use App\Service\SwissPostService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\ResultSetMapping;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for search XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
class AjaxSearchController extends AbstractController
{
    /**
     * Search address.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/address', name: 'ajax_search_address')]
    public function searchAddress(
        SwissPostService $service,
        #[MapQueryParameter]
        string $zip = null,
        #[MapQueryParameter]
        string $city = null,
        #[MapQueryParameter]
        string $street = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        $limit ??= 15;
        if (!empty($zip)) {
            return $this->json($service->findZip($zip, $limit));
        }
        if (!empty($city)) {
            return $this->json($service->findCity($city, $limit));
        }
        if (!empty($street)) {
            return $this->json($service->findStreet($street, $limit));
        }

        return $this->json([]);
    }

    /**
     * Search distinct calculation's customers in existing calculations.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/customer', name: 'ajax_search_customer')]
    public function searchCustomer(
        CalculationRepository $repository,
        #[MapQueryParameter]
        string $query = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        return $this->getValuesFromRepository($repository, 'customer', $query, $limit);
    }

    /**
     * Search products.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/product', name: 'ajax_search_product')]
    public function searchProduct(
        ProductRepository $repository,
        #[MapQueryParameter]
        string $query = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        return $this->getValuesFromCallback(
            fn (string $query, int $limit): array => $repository->search($query, $limit),
            $query,
            $limit
        );
    }

    /**
     * Search distinct product and task suppliers.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/supplier', name: 'ajax_search_supplier')]
    public function searchSupplier(
        EntityManagerInterface $manager,
        #[MapQueryParameter]
        string $query = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        return $this->getValuesFromManager($manager, 'supplier', $query, $limit);
    }

    /**
     * Search distinct customer's titles.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/title', name: 'ajax_search_title')]
    public function searchTitle(
        CustomerRepository $repository,
        #[MapQueryParameter]
        string $query = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        return $this->getValuesFromRepository($repository, 'title', $query, $limit);
    }

    /**
     * Search distinct units from products and tasks.
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[GetRoute(path: '/search/unit', name: 'ajax_search_unit')]
    public function searchUnit(
        EntityManagerInterface $manager,
        #[MapQueryParameter]
        string $query = null,
        #[MapQueryParameter(flags: \FILTER_NULL_ON_FAILURE)]
        int $limit = null
    ): JsonResponse {
        return $this->getValuesFromManager($manager, 'unit', $query, $limit);
    }

    private function getDistinctSql(string $field, string $query, int $limit): string
    {
        return <<<SQL
                SELECT DISTINCT
                    p.$field
                FROM
                    sy_Product as p
                WHERE
                    p.$field LIKE '%$query%'
                UNION
                SELECT DISTINCT
                    t.$field
                FROM
                    sy_Task as t
                WHERE
                    t.$field LIKE '%$query%'
                ORDER BY
                    $field
                LIMIT $limit
            SQL;
    }

    /**
     * Search distinct values within the given callback.
     *
     * @psalm-param callable(string, int): array $callback
     */
    private function getValuesFromCallback(
        callable $callback,
        string $query = null,
        int $limit = null
    ): JsonResponse {
        if (null === $query || '' === $query) {
            return $this->jsonFalse(['values' => []]);
        }

        try {
            $values = $callback($query, $limit ?? 15);
            if ([] !== $values) {
                return $this->json($values);
            }

            return $this->jsonFalse(['values' => []]);
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }
    }

    /**
     * Search distinct values from products and tasks.
     */
    private function getValuesFromManager(EntityManagerInterface $manager, string $field, string $query = null, int $limit = null): JsonResponse
    {
        return $this->getValuesFromCallback(
            function (string $query, int $limit) use ($manager, $field): array {
                $sql = $this->getDistinctSql($field, $query, $limit);

                return $manager->createNativeQuery($sql, new ResultSetMapping())
                    ->getSingleColumnResult();
            },
            $query,
            $limit
        );
    }

    /**
     * Search distinct values from the given repository.
     *
     * @template T of \App\Entity\AbstractEntity
     *
     * @param AbstractRepository<T> $repository
     */
    private function getValuesFromRepository(AbstractRepository $repository, string $field, string $query = null, int $limit = null): JsonResponse
    {
        return $this->getValuesFromCallback(
            fn (string $query, int $limit): array => $repository->getDistinctValues($field, $query, $limit),
            $query,
            $limit
        );
    }
}
