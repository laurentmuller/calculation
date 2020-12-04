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
use App\Util\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Person;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to generate entities.
 *
 * @author Laurent Muller
 *
 * @Route("/generate")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class GeneratorController extends AbstractController
{
    /**
     * @Route("", name="generate")
     */
    public function generate(): Response
    {
        $params = ['count' => 0];
        $type = UrlGeneratorInterface::ABSOLUTE_URL;

        // fields
        $helper = $this->createFormHelper('generate.fields.', ['count' => 1]);
        $helper->field('entity')
            ->addChoiceType([
                'customer.name' => $this->generateUrl('generate_customer', $params, $type),
                'calculation.name' => $this->generateUrl('generate_calculation', $params, $type),
        ]);

        $helper->field('count')
            ->updateOption('html5', true)
            ->updateAttribute('min', 1)
            ->updateAttribute('max', 50)
            ->addNumberType(0);

        $helper->field('confirm')
            ->notMapped()
            ->updateAttribute('data-error', $this->trans('generate.error.confirm'))
            ->addCheckboxType();

        return $this->render('admin/generate.html.twig', [
            'form' => $helper->createView(),
        ]);
    }

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
                if ($item->isEmptyPrice()) {
                    $item->setPrice($faker->randomFloat(2, 1, 10));
                }

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
                    'id' => FormatUtils::formatId($c->getId()),
                    'date' => FormatUtils::formatDate($c->getDate()),
                    'state' => $c->getStateCode(),
                    'description' => $c->getDescription(),
                    'customer' => $c->getCustomer(),
                    'total' => FormatUtils::formatAmount($c->getOverallTotal()),
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
     * Sort products by category code then by description.
     *
     * @param Product[] $products the products to sort
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
