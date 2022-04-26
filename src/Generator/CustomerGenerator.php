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
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Person;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class to generate customers.
 */
class CustomerGenerator extends AbstractEntityGenerator
{
    /**
     * {@inheritDoc}
     */
    protected function generateEntities(int $count, bool $simulate, EntityManagerInterface $manager, Generator $generator): JsonResponse
    {
        $customers = [];
        $styles = [0, 1, 2];
        $genders = [Person::GENDER_MALE, Person::GENDER_FEMALE];

        for ($i = 0; $i < $count; ++$i) {
            $customer = new Customer();
            $style = (int) $generator->randomElement($styles);
            $gender = (string) $generator->randomElement($genders);

            switch ($style) {
                    case 0: // company
                        $customer->setCompany($generator->company())
                            ->setEmail($generator->companyEmail());
                        break;

                    case 1: // contact
                        $customer->setTitle($generator->title($gender))
                            ->setFirstName($generator->firstName($gender))
                            ->setLastName($generator->lastName())
                            ->setEmail($generator->email());
                        break;

                    default: // both
                        $customer->setCompany($generator->company())
                            ->setFirstName($generator->firstName($gender))
                            ->setTitle($generator->title($gender))
                            ->setLastName($generator->lastName())
                            ->setEmail($generator->email());
                        break;
                }

            $customer->setAddress($generator->streetAddress())
                ->setZipCode($generator->postcode())
                ->setCity($generator->city());

            // save
            if (!$simulate) {
                $manager->persist($customer);
            }

            // add
            $customers[] = $customer;
        }

        // save
        if (!$simulate) {
            $manager->flush();
        }

        // map
        $items = \array_map(static function (Customer $c): array {
            return [
                    'id' => $c->getId(),
                    'company' => $c->getCompany(),
                    'firstName' => $c->getFirstName(),
                    'lastName' => $c->getLastName(),
                    'fullName' => $c->getFullName(),
                    'nameAndCompany' => $c->getNameAndCompany(),
                    'address' => $c->getAddress(),
                    'zipCode' => $c->getZipCode(),
                    'city' => $c->getCity(),
                    'zipCity' => $c->getZipCity(),
                ];
        }, $customers);

        return new JsonResponse([
                'result' => true,
                'items' => $items,
                'count' => \count($items),
                'simulate' => $simulate,
                'message' => $this->trans('counters.customers_generate', ['count' => $count]),
            ]);
    }
}
