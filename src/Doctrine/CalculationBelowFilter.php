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

namespace App\Doctrine;

use App\Entity\Calculation;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;

/**
 * SQL filter to get calculations below the minimum margin.
 */
class CalculationBelowFilter extends SQLFilter
{
    /** The filter name. */
    public const string FILTER_NAME = 'below_filter';

    /** The parameter name for the minimum margin. */
    public const string MARGIN_PARAMETER = 'minMargin';

    /**
     * @param ClassMetadata<Calculation> $targetEntity
     */
    #[\Override]
    public function addFilterConstraint(ClassMetadata $targetEntity, string $targetTableAlias): string
    {
        if (Calculation::class !== $targetEntity->getName()) {
            return '';
        }

        $minMargin = $this->getMinMargin();
        $itemsField = $this->getColumnName($targetEntity, $targetTableAlias, 'itemsTotal');
        $overallField = $this->getColumnName($targetEntity, $targetTableAlias, 'overallTotal');

        return \sprintf(
            '%1$s != 0 AND (%2$s / %1$s) < %3$s',
            $itemsField,
            $overallField,
            $minMargin
        );
    }

    /**
     * @param ClassMetadata<Calculation> $targetEntity
     */
    private function getColumnName(ClassMetadata $targetEntity, string $targetTableAlias, string $fieldName): string
    {
        return \sprintf('%s.%s', $targetTableAlias, $targetEntity->getColumnName($fieldName));
    }

    private function getMinMargin(): string
    {
        return \trim($this->getParameter(self::MARGIN_PARAMETER), '\'"');
    }
}
