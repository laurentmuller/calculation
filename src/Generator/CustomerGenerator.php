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

namespace App\Generator;

use App\Entity\Customer;
use App\Faker\Generator;
use Faker\Provider\Person;

/**
 * Class to generate customers.
 *
 * @extends AbstractEntityGenerator<Customer>
 */
class CustomerGenerator extends AbstractEntityGenerator
{
    /**
     * {@inheritDoc}
     */
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        $styles = [0, 1, 2];
        $genders = [Person::GENDER_MALE, Person::GENDER_FEMALE];
        for ($i = 0; $i < $count; ++$i) {
            $entities[] = $this->createEntity($styles, $genders, $generator);
        }

        return $entities;
    }

    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.customers_generate', ['count' => $count]);
    }

    protected function mapEntity($entity): array
    {
        return [
            'nameAndCompany' => $entity->getNameAndCompany(),
            'address' => $entity->getAddress(),
            'zipCity' => $entity->getZipCity(),
        ];
    }

    private function createEntity(array $styles, array $genders, Generator $generator): Customer
    {
        $style = (int) $generator->randomElement($styles);
        $gender = (string) $generator->randomElement($genders);

        return $this->generateEntity($style, $gender, $generator)
            ->setAddress($generator->streetAddress())
            ->setZipCode($generator->postcode())
            ->setCity($generator->city());
    }

    private function generateEntity(int $style, string $gender, Generator $generator): Customer
    {
        $entity = new Customer();
        match ($style) {
            // company
            0 => $entity->setCompany($generator->company())
                ->setEmail($generator->companyEmail()),
            // contact
            1 => $entity->setTitle($generator->title($gender))
                ->setFirstName($generator->firstName($gender))
                ->setLastName($generator->lastName())
                ->setEmail($generator->email()),
            // both
            default => $entity->setCompany($generator->company())
                ->setFirstName($generator->firstName($gender))
                ->setTitle($generator->title($gender))
                ->setLastName($generator->lastName())
                ->setEmail($generator->email())
        };

        return $entity;
    }
}
