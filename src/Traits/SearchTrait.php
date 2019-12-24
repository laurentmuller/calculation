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

namespace App\Traits;

use Behat\Transliterator\Transliterator;

/**
 * Trait for search functionality.
 *
 * @author Laurent Muller
 */
trait SearchTrait
{
    /**
     * Returns if one of this terms match the given term.
     *
     * @param string $query the search term
     *
     * @return bool true if match
     */
    public function match(string $query): bool
    {
        $query = Transliterator::unaccent($query);
        $terms = $this->getSearchTerms();
        foreach ($terms as $term) {
            if (null !== $term && false !== \stripos(Transliterator::unaccent($term), $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Gets the search terms.
     *
     * @return string[]
     */
    abstract protected function getSearchTerms(): array;
}
