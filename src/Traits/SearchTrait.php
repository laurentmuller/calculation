<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Traits;

use Symfony\Component\String\AbstractUnicodeString;
use Symfony\Component\String\UnicodeString;

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
        $terms = $this->getSearchTerms();
        $query = $this->ascii($query)->toString();
        foreach ($terms as $term) {
            if (null !== $term && $this->ignoreCase($term)->containsAny($query)) {
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

    /**
     * Converts the given string to ASCII transliteration.
     */
    private function ascii(string $value): AbstractUnicodeString
    {
        return (new UnicodeString($value))->ascii();
    }

    /**
     * Converts the given string to ASCII transliteration and ignore case.
     */
    private function ignoreCase(string $value): AbstractUnicodeString
    {
        return $this->ascii($value)->ignoreCase();
    }
}
