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

namespace App\Report;

use App\Controller\AbstractController;

/**
 * Asbtract report to render an array of objects.
 *
 * @author Laurent Muller
 */
abstract class AbstractArrayReport extends AbstractReport
{
    /**
     * @var array
     */
    protected $entities;

    /**
     * Constructor.
     *
     * @param AbstractController $controller  the parent controller
     * @param array              $entities    the entities to render
     * @param string             $orientation the page orientation. One of the ORIENTATION_XX contants.
     */
    public function __construct(AbstractController $controller, array $entities, string $orientation = self::ORIENTATION_PORTRAIT)
    {
        parent::__construct($controller, $orientation);
        $this->entities = $entities;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        if (!empty($this->entities)) {
            return $this->doRender($this->entities);
        }

        return false;
    }

    /**
     * Render the given entities.
     *
     * @param array $entities the entities to render
     *
     * @return bool true if rendered successfully; false otherwise
     */
    abstract protected function doRender(array $entities): bool;
}
