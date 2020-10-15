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
 * @Route("/generate")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class GeneratorController extends AbstractController
{
    /**
     * Create one or more calculations with random data.
     *
     * @Route("/calculation/{count}", name="generate_calculation", requirements={"count": "\d+" })
     */
    public function generateCalculation(EntityManagerInterface $manager, CalculationService $service, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        // dates
        $dateStart = $this->getDateStart();
        $dateEnd = $this->getDateEnd();

        // load data
        $states = $this->getCalculationState($manager);
        $products = $this->getProducts($manager);
        $users = $this->getUsers($manager);

        // product range
        $min = \min(5, \count($products));
        $max = \min(15, \count($products));

        $calculations = [];
        for ($i = 0; $i < $count; ++$i) {
            // calculation
            $calculation = new Calculation();
            $calculation->setDate($faker->dateTimeBetween($dateStart, $dateEnd))
                ->setDescription($faker->catchPhrase())
                ->setUserMargin($faker->randomFloat(2, 0, 0.1))
                ->setState($faker->randomElement($states))
                ->setCreatedBy($faker->randomElement($users))
                ->setCustomer($faker->name);

            /** @var Product[] $itemProducts */
            $itemProducts = $faker->randomElements($products, $faker->numberBetween($min, $max));
            $this->sortProducts($itemProducts);

            // add products
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
     * @Route("/customer/{count}", name="generate_customer", requirements={"count": "\d+" })
     */
    public function generateCustomer(EntityManagerInterface $manager, FakerService $fakerService, int $count = 1): JsonResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $fakerService->getFaker();

        $customers = [];
        $styles = [0, 1, 2];
        $genders = $this->getGenders();

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

        $data = [
            'result' => true,
            'count' => $count,
            'customers' => $customers,
            'message' => $this->trans('counters.customers_generate', ['count' => $count]),
        ];

        return $this->json($data);
    }

    /**
     * @Route("", name="generate_index")
     */
    public function index(): Response
    {
        $params = ['count' => 0];
        $type = UrlGeneratorInterface::ABSOLUTE_URL;

        // fields
        $helper = $this->createFormHelper('generate.fields.', ['count' => 1]);
        $helper->field('entity')
            ->addChoiceType([
                'calculation.name' => $this->generateUrl('generate_calculation', $params, $type),
                'customer.name' => $this->generateUrl('generate_customer', $params, $type),
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
     * Update calculations with random customers.
     *
     * @Route("/calculation/update", name="generate_calculation_update")
     */
    public function updateCalculation(EntityManagerInterface $manager, FakerService $service): RedirectResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $manager->getRepository(Calculation::class)->findAll();
        $states = $this->getCalculationState($manager);
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
        $manager->flush();

        $count = \count($calculations);
        $this->infoTrans('counters.calculations_update', ['count' => $count]);

        return $this->redirectToHomePage();
    }

    /**
     * Update customers with random values.
     *
     * @Route("/customer/update", name="generate_customer_update")
     */
    public function updateCustomer(EntityManagerInterface $manager, FakerService $service): RedirectResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        /** @var \App\Entity\Customer[] $customers */
        $customers = $manager->getRepository(Customer::class)->findAll();
        $genders = $this->getGenders();

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
        $this->infoTrans('counters.customers_update', ['count' => $count]);

        return $this->redirectToHomePage();
    }

    /**
     * Gets the calculation states.
     *
     * @return CalculationState[]
     */
    private function getCalculationState(EntityManagerInterface $manager): array
    {
        return $manager->getRepository(CalculationState::class)->findBy([
            'editable' => true,
        ]);
    }

    /**
     * Gets the last day of the next month.
     */
    private function getDateEnd(): \DateTime
    {
        $date = new \DateTime();
        $date->modify('last day of this month');
        $interval = new \DateInterval('P1M');
        $date = $date->add($interval);

        return $date;
    }

    /**
     * Gets the first day of 3 months before.
     */
    private function getDateStart(): \DateTime
    {
        $date = new \DateTime();
        $date->modify('first day of this month');
        $interval = new \DateInterval('P3M');
        $date = $date->sub($interval);

        return $date;
    }

    /**
     * Gets the genders.
     *
     * @return string[]
     */
    private function getGenders()
    {
        return [Person::GENDER_MALE, Person::GENDER_FEMALE];
    }

    /**
     * Gets the products.
     *
     * @return Product[]
     */
    private function getProducts(EntityManagerInterface $manager): array
    {
        return $manager->getRepository(Product::class)->findAll();
    }

    /**
     * Gets the enabled user names.
     *
     * @return string[]
     */
    private function getUsers(EntityManagerInterface $manager): array
    {
        $users = $manager->getRepository(User::class)->findBy([
            'enabled' => true,
        ]);

        return \array_map(function (User $user) {
            return $user->getUsername();
        }, $users);
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

    /**
     * Sort products by category code then by description.
     *
     * @param Product[] $products
     */
    private function sortProducts(array &$products): void
    {
        \usort($products, function (Product $a, Product $b) {
            $result = \strcasecmp($a->getCategoryCode(), $b->getCategoryCode());
            if (0 === $result) {
                return \strcasecmp($a->getDescription(), $b->getDescription());
            }

            return $result;
        });
    }
}
