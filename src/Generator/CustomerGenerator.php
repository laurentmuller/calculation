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
use App\Interfaces\EntityInterface;
use Faker\Provider\Person;

/**
 * Class to generate customers.
 *
 * @extends AbstractEntityGenerator<Customer>
 */
class CustomerGenerator extends AbstractEntityGenerator
{
    private const int STYLE_BOTH = 0;
    private const int STYLE_COMPANY = 1;
    private const int STYLE_CONTACT = 2;

    #[\Override]
    protected function createEntities(int $count, bool $simulate, Generator $generator): array
    {
        $entities = [];
        for ($i = 0; $i < $count; ++$i) {
            $entities[] = $this->createEntity(
                $this->randomStyle($generator),
                $this->randomGender($generator),
                $generator
            );
        }

        return $entities;
    }

    #[\Override]
    protected function getCountMessage(int $count): string
    {
        return $this->trans('counters.customers_generate', ['count' => $count]);
    }

    #[\Override]
    protected function mapEntity(EntityInterface $entity): array
    {
        return [
            'nameAndCompany' => $entity->getNameAndCompany(),
            'address' => $entity->getAddress(),
            'zipCity' => $entity->getZipCity(),
        ];
    }

    /**
     * @phpstan-param self::STYLE_*    $style
     * @phpstan-param Person::GENDER_* $gender
     */
    private function createEntity(int $style, string $gender, Generator $generator): Customer
    {
        $entity = new Customer();
        $entity->setAddress($generator->streetAddress())
            ->setZipCode($generator->postcode())
            ->setCity($generator->city());

        return match ($style) {
            self::STYLE_COMPANY => $entity->setCompany($generator->company())
                ->setEmail($generator->companyEmail()),
            self::STYLE_CONTACT => $entity->setTitle($generator->title($gender))
                ->setFirstName($generator->firstName($gender))
                ->setLastName($generator->lastName())
                ->setEmail($generator->email()),
            default => $entity->setCompany($generator->company())
                ->setFirstName($generator->firstName($gender))
                ->setTitle($generator->title($gender))
                ->setLastName($generator->lastName())
                ->setEmail($generator->email())
        };
    }

    /**
     * @phpstan-return Person::GENDER_*
     */
    private function randomGender(Generator $generator): string
    {
        return $generator->randomElement([Person::GENDER_MALE, Person::GENDER_FEMALE]);
    }

    /**
     * @phpstan-return self::STYLE_*
     */
    private function randomStyle(Generator $generator): int
    {
        return $generator->randomElement([self::STYLE_COMPANY, self::STYLE_CONTACT, self::STYLE_BOTH]);
    }
}
