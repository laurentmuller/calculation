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

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for customer entity.
 *
 * @template-extends AbstractRepository<Customer>
 *
 * @author Laurent Muller
 */
class CustomerRepository extends AbstractRepository
{
    /**
     * The first name, last name and company field name.
     */
    final public const NAME_COMPANY_FIELD = 'nameAndCompany';

    /**
     * The first name, last name and company fields.
     */
    final public const NAME_COMPANY_FIELDS = ['lastName', 'firstName', 'company'];

    /**
     * The zip and city field name.
     */
    final public const ZIP_CITY_FIELD = 'zipCity';

    /**
     * The zip code and city fields.
     */
    final public const ZIP_CITY_FIELDS = ['zipCode', 'city'];

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
     * Gets all customers order by name and company.
     *
     * @return Customer[]
     */
    public function findAllByNameAndCompany(): array
    {
        $fields = $this->concat(self::DEFAULT_ALIAS, self::NAME_COMPANY_FIELDS, 'ZZZ');

        /** @psalm-var Customer[] $result */
        $result = $this->createQueryBuilder(self::DEFAULT_ALIAS)
            ->orderBy($fields, Criteria::ASC)
            ->getQuery()
            ->getResult();

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFields(string $field, string $alias = self::DEFAULT_ALIAS): array|string
    {
        return match ($field) {
            self::NAME_COMPANY_FIELD => $this->addPrefixes($alias, self::NAME_COMPANY_FIELDS),
            self::ZIP_CITY_FIELD => $this->addPrefixes($alias, self::ZIP_CITY_FIELDS),
            default => parent::getSearchFields($field, $alias),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function getSortField(string $field, string $alias = self::DEFAULT_ALIAS): string
    {
        return match ($field) {
            self::NAME_COMPANY_FIELD => $this->concat($alias, self::NAME_COMPANY_FIELDS),
            self::ZIP_CITY_FIELD => $this->concat($alias, self::ZIP_CITY_FIELDS),
            default => parent::getSortField($field, $alias),
        };
    }
}
