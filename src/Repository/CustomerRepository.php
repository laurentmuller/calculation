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

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * Repository for customer entity.
 *
 * @author Laurent Muller
 *
 * @see \App\Entity\Customer
 */
class CustomerRepository extends BaseRepository
{
    /**
     * The name and company fields.
     */
    private static $NAME_COMPANY_FIELDS = ['lastName', 'firstName', 'company'];

    /**
     * The zip code and city fields.
     */
    private static $ZIP_CITY_FIELDS = ['zipCode', 'city'];

    /**
     * Constructor.
     *
     * @param ManagerRegistry $registry The connections and entity managers registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'nameAndCompany':
                return $this->addPrefixes($alias, self::$NAME_COMPANY_FIELDS);
            case 'zipCity':
                return $this->addPrefixes($alias, self::$ZIP_CITY_FIELDS);
            default:
                return parent::getSearchFields($field, $alias);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getSortFields(string $field, string $alias = self::DEFAULT_ALIAS)
    {
        switch ($field) {
            case 'nameAndCompany':
                return $this->concat($alias, self::$NAME_COMPANY_FIELDS);
            case 'zipCity':
                return $this->concat($alias, self::$ZIP_CITY_FIELDS);
            default:
                return parent::getSortFields($field, $alias);
        }
    }

    /**
     * Add alias to the given fields.
     *
     * @param string   $alias the entity alias
     * @param string[] $names the fields to add alias
     *
     * @return string[] the fields with alias
     */
    private function addPrefixes(string $alias, array $names): array
    {
        return \array_map(function (string $name) use ($alias) {
            return "{$alias}.{$name}";
        }, $names);
    }

    /**
     * Concat fields.
     *
     * @param string   $alias  the entity prefix
     * @param string[] $fields the fields to concat
     *
     * @return string the concatened fields
     */
    private function concat(string $alias, array $fields): string
    {
        foreach ($fields as &$field) {
            $field = "COALESCE($alias.$field, '')";
        }

        return 'CONCAT(' . \implode(', ', $fields) . ')';
    }
}
