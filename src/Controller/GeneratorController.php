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

namespace App\Controller;

use App\Entity\Calculation;
use App\Entity\CalculationItem;
use App\Entity\CalculationState;
use App\Entity\Customer;
use App\Entity\Product;
use App\Entity\User;
use App\Service\CalculationService;
use App\Service\FakerService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Person;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to generate or update entities.
 *
 * @author Laurent Muller
 *
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class GeneratorController extends AbstractController
{
    /**
     * @Route("/generate", name="generate")
     */
    public function generate(UrlGeneratorInterface $generator): Response
    {
        $params = ['count' => 0];
        $type = UrlGeneratorInterface::ABSOLUTE_URL;

        $data = [
            'entity' => $this->generateUrl('calculation_generate', $params, $type),
            'count' => 1,
        ];

        // fields
        $helper = $this->createFormHelper('generate.fields.', $data);
        $helper->field('entity')
            ->addChoiceType([
                'calculation.name' => $this->generateUrl('calculation_generate', $params, $type),
                'customer.name' => $this->generateUrl('customer_generate', $params, $type),
        ]);
        $helper->field('count')
            ->updateOption('html5', true)
            ->updateAttribute('min', 1)
            ->updateAttribute('max', 50)
            ->addNumberType(0);

        return $this->render('admin/generate.html.twig', [
            'form' => $helper->createView(),
        ]);
    }

    /**
     * Create one or more calculations with random data.
     *
     * @Route("/calculation/generate/{count}", name="calculation_generate", requirements={"count": "\d+" })
     */
    public function generateCalculation(EntityManagerInterface $manager, CalculationService $service, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        // 3 months before
        $startDate = new \DateTime();
        $startDate->modify('first day of this month');
        $interval = new \DateInterval('P3M');
        $startDate = $startDate->sub($interval);

        // end of next month
        $endDate = new \DateTime();
        $endDate->modify('last day of this month');
        $interval = new \DateInterval('P1M');
        $endDate = $endDate->add($interval);

        // load data
        $products = $manager->getRepository(Product::class)->findAll();
        $states = $manager->getRepository(CalculationState::class)->findBy([
            'editable' => true,
        ]);
        $users = $manager->getRepository(User::class)->findBy([
            'enabled' => true,
        ]);
        $users = \array_map(function (User $user) {
            return $user->getUsername();
        }, $users);

        $calculations = [];
        for ($i = 0; $i < $count; ++$i) {
            // calculation
            $calculation = new Calculation();
            $calculation->setDate($faker->dateTimeBetween($startDate, $endDate))
                ->setDescription($faker->catchPhrase())
                ->setUserMargin($faker->randomFloat(2, 0, 0.1))
                ->setState($faker->randomElement($states))
                ->setCreatedBy($faker->randomElement($users))
                ->setCustomer($faker->name);

            // items
            /** @var Product[] $itemProducts */
            $itemProducts = $faker->randomElements($products, $faker->numberBetween(5, 15));
            foreach ($itemProducts as $product) {
                // copy
                $item = CalculationItem::create($product)->setQuantity($faker->numberBetween(1, 10));

                // find group
                $category = $product->getCategory();
                $group = $calculation->findGroup($category, true);

                // add
                $group->addItem($item);
            }

            // update
            $service->updateTotal($calculation);

            // save
            $manager->persist($calculation);

            // add
            $calculations[] = $calculation;
        }

        // commit
        $manager->flush();

        // serialize
        $calculations = \array_map(function (Calculation $c) {
            return [
                    'id' => $this->localeId($c->getId()),
                    'date' => $this->localeDate($c->getDate()),
                    'state' => $c->getStateCode(),
                    'description' => $c->getDescription(),
                    'customer' => $c->getCustomer(),
                    'total' => $this->localeAmount($c->getOverallTotal()),
                ];
        }, $calculations);

        $count = \count($calculations);
        $data = [
            'result' => true,
            'count' => $count,
            'calculations' => $calculations,
            'message' => $this->trans('counters.calculations_generate', ['count' => $count]),
        ];

        return $this->json($data);
    }

    /**
     * Create one or more customers with random data.
     *
     * @Route("/customer/generate/{count}", name="customer_generate", requirements={"count": "\d+" })
     */
    public function generateCustomer(EntityManagerInterface $manager, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        $customers = [];
        $styles = [0, 1, 2];
        $genders = [Person::GENDER_MALE, Person::GENDER_FEMALE];

        for ($i = 0; $i < $count; ++$i) {
            $customer = new Customer();
            $style = $faker->randomElement($styles);
            $gender = $faker->randomElement($genders);

            switch ($style) {
                case 0: // company
                    $customer->setCompany($faker->company)
                        ->setEmail($faker->companyEmail);
                    break;

                case 1: // contact
                    $customer->setTitle($faker->title($gender))
                        ->setFirstName($faker->firstName($gender))
                        ->setLastName($faker->lastName)
                        ->setEmail($faker->email);
                    break;

                default: // both
                    $customer->setCompany($faker->company)
                        ->setFirstName($faker->firstName($gender))
                        ->setTitle($faker->title($gender))
                        ->setLastName($faker->lastName)
                        ->setEmail($faker->email);
                    break;
            }
            $customer->setAddress($faker->streetAddress)
                ->setZipCode($faker->postcode)
                ->setCity($faker->city);

            // save
            $manager->persist($customer);

            // add
            $customers[] = $customer;
        }

        // commit
        $manager->flush();

        // serialize
        $customers = \array_map(function (Customer $c) {
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

        $count = \count($customers);
        $data = [
            'result' => true,
            'count' => $count,
            'customers' => $customers,
            'message' => $this->trans('counters.customers_generate', ['count' => $count]),
        ];

        return $this->json($data);
    }

    /**
     * Update calculations with random customers.
     *
     * @Route("/calculation/update", name="calculation_update")
     */
    public function updateCalculation(EntityManagerInterface $manager, FakerService $service): RedirectResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $manager->getRepository(Calculation::class)->findAll();
        $states = $manager->getRepository(CalculationState::class)->findBy([
            'editable' => true,
        ]);
        $styles = [0, 1, 2];

        // update
        foreach ($calculations as $calculation) {
            $style = $faker->randomElement($styles);
            switch ($style) {
                case 0:
                    $calculation->setCustomer($faker->companySuffix);
                    break;

                case 1:
                    $calculation->setCustomer($faker->name(Person::GENDER_MALE));
                    break;

                default:
                    $calculation->setCustomer($faker->name(Person::GENDER_FEMALE));
                    break;
            }
            $calculation->setDescription($faker->catchPhrase())
                ->setState($faker->randomElement($states));
        }

        // save
        $manager->flush();

        $count = \count($calculations);
        $this->info("La mise à jour de {$count} calculations a été effectuée avec succès.");

        return $this->redirectToHomePage();
    }

    /**
     * Update customers with random values.
     *
     * @Route("/customer/update", name="customer_update")
     */
    public function updateCustomer(EntityManagerInterface $manager, FakerService $service): RedirectResponse
    {
        /** @var \App\Entity\Customer[] $customers */
        $customers = $manager->getRepository(Customer::class)->findAll();

        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        $genders = [
            Person::GENDER_MALE,
            Person::GENDER_FEMALE,
        ];

        $accessor = new PropertyAccessor();
        foreach ($customers as $customer) {
            $gender = $faker->randomElement($genders);
            $this->replace($accessor, $customer, 'title', $faker->title($gender))
                ->replace($accessor, $customer, 'firstName', $faker->firstName($gender))
                ->replace($accessor, $customer, 'lastName', $faker->lastName)
                ->replace($accessor, $customer, 'company', $faker->companySuffix)
                ->replace($accessor, $customer, 'address', $faker->streetAddress)
                ->replace($accessor, $customer, 'zipCode', $faker->postcode)
                ->replace($accessor, $customer, 'city', $faker->city)
                ->replace($accessor, $customer, 'email', $faker->email);
        }

        $manager->flush();

        $count = \count($customers);
        $this->info("La mise à jour de {$count} clients a été effectuée avec succès.");

        return $this->redirectToHomePage();
    }

    /**
     * Update an element property.
     *
     * @param PropertyAccessor $accessor the property accessor to get or set value
     * @param mixed            $element  the element to update
     * @param string           $property the property name
     * @param mixed            $value    the new value to set
     */
    private function replace(PropertyAccessor $accessor, $element, string $property, $value): self
    {
        if (empty($accessor->getValue($element, $property))) {
            $value = null;
        }
        $accessor->setValue($element, $property, $value);

        return $this;
    }
}
