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

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;

/**
 * Service to create SQL queries.
 *
 * @author Laurent Muller
 */
class SqlBuilderService
{
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $manager the manager to query
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    public function getChildJoin(string $className, string $fieldName): string
    {
        if ($association = $this->getAssociation($className, $fieldName)) {
            if (isset($association['joinColumns']) && !empty($association['joinColumns'])) {
                $join = $association['joinColumns'][0];

                $sourceEntity = $association['sourceEntity'];
                $sourceTable = $this->getTableName($sourceEntity);

                $targetEntity = $association['targetEntity'];
                $targetTable = $this->getTableName($targetEntity);

                $sourceField = $join['name'];
                $targetField = $join['referencedColumnName'];

                return "INNER JOIN {$sourceTable} ON {$sourceTable}.{$sourceField} = {$targetTable}.{$targetField} ";
            }
        }

        return '';
    }

    public function getParentJoin(string $className, string $fieldName): string
    {
        if ($association = $this->getAssociation($className, $fieldName)) {
            if (isset($association['joinColumns']) && !empty($association['joinColumns'])) {
                $join = $association['joinColumns'][0];

                $sourceEntity = $association['sourceEntity'];
                $sourceTable = $this->getTableName($sourceEntity);

                $targetEntity = $association['targetEntity'];
                $targetTable = $this->getTableName($targetEntity);

                $sourceField = $join['name'];
                $targetField = $join['referencedColumnName'];

                return "INNER JOIN {$targetTable} ON {$sourceTable}.{$sourceField} = {$targetTable}.{$targetField} ";
            }
        }

        return '';
    }

    /**
     * Gets the table name.
     *
     * @param string $className the class name
     *
     * @return string the table name
     */
    public function getTableName(string $className): string
    {
        return $this->getClassMetadata($className)->getTableName();
    }

    private function getAssociation(string $className, string $fieldName): ?array
    {
        $data = $this->getClassMetadata($className);
        if ($data->hasAssociation($fieldName)) {
            return $data->getAssociationMapping($fieldName);
        }

        return null;
    }

    private function getClassMetadata(string $className): ClassMetadata
    {
        return $this->manager->getClassMetadata($className);
    }
}
