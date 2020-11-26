<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Controller;

use App\DataTable\SearchDataTable;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\Product;
use App\Interfaces\EntityVoterInterface;
use App\Util\Utils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller to display the search page.
 *
 * @author Laurent Muller
 */
class SearchController extends AbstractController
{
    /**
     * Render datatable for native query search.
     *
     * @param Request         $request the request to get parameters
     * @param SearchDataTable $table   the datatable
     *
     * @Route("/search", name="search")
     * @IsGranted("ROLE_USER")
     */
    public function search(Request $request, SearchDataTable $table): Response
    {
        $results = $table->handleRequest($request);
        if ($table->isCallback()) {
            return $this->json($results);
        }

        // entities
        $entities = $this->getEntities();

        // attributes
        $attributes = [
            'edit-action' => \json_encode($this->getApplication()->isEditAction()),
        ];

        // authorizations
        $show_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_SHOW);
        $edit_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_EDIT);
        $delete_granted = $table->isActionGranted(EntityVoterInterface::ATTRIBUTE_DELETE);

        // render
        $parameters = [
            'results' => $results,
            'entities' => $entities,
            'attributes' => $attributes,
            'columns' => $table->getColumns(),
            'show_granted' => $show_granted,
            'edit_granted' => $edit_granted,
            'delete_granted' => $delete_granted,
        ];

        return $this->render('home/search.html.twig', $parameters);
    }

    /**
     * Gets the entities class and name.
     *
     * @return string[]
     */
    private function getEntities(): array
    {
        $entities = [
            $this->getEntityName(Calculation::class) => 'calculation.name',
            $this->getEntityName(Product::class) => 'product.name',
            $this->getEntityName(Category::class) => 'category.name',
            $this->getEntityName(CalculationState::class) => 'calculationstate.name',
        ];
        if ($this->isDebug()) {
            $entities[$this->getEntityName(Customer::class)] = 'customer.name';
        }

        return $entities;
    }

    /**
     * Gets the entity name for the given class.
     *
     * @param string $class the entity class
     *
     * @return string the entity name
     */
    private function getEntityName(string $class): string
    {
        return \strtolower(Utils::getShortName($class));
    }
}
