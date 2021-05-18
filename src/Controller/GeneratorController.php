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

namespace App\Controller;

use App\Entity\Calculation;
use App\Entity\CalculationItem;
use App\Entity\Customer;
use App\Service\CalculationService;
use App\Service\FakerService;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Person;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
        $helper = $this->createFormHelper('generate.fields.', [
            'count' => 1,
            'simulate' => $this->isSessionBool('admin.generate.simulate', true),
        ]);

        $helper->field('entity')
            ->addChoiceType([
                'customer.name' => $this->generateUrl('generate_customer'),
                'calculation.name' => $this->generateUrl('generate_calculation'),
        ]);

        $helper->field('count')
            ->updateAttribute('min', 1)
            ->updateAttribute('max', 20)
            ->addNumberType(0);

        $helper->field('simulate')
            ->help('generate.help.simulate')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

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
     * @Route("/calculation", name="generate_calculation")
     */
    public function generateCalculation(Request $request, EntityManagerInterface $manager, CalculationService $service, FakerService $fakerService, LoggerInterface $logger): JsonResponse
    {
        try {
            $calculations = [];
            $faker = $fakerService->getFaker();
            $count = $this->getRequestInt($request, 'count');
            $simulate = $this->getRequestBoolean($request, 'simulate', true);
            $id = $simulate ? (int) $manager->getRepository(Calculation::class)->getNextId() : 0;

            // products range
            $countProducts = $faker->countProducts(); /* @phpstan-ignore-line */
            $min = \min(5, $countProducts);
            $max = \min(15, $countProducts);

            for ($i = 0; $i < $count; ++$i) {
                $state = $faker->state(); /* @phpstan-ignore-line */
                $userName = $faker->userName(); /* @phpstan-ignore-line */
                $date = $faker->dateTimeBetween('first day of previous month', 'last day of next month');

                $calculation = new Calculation();
                $calculation->setDate($date)
                    ->setDescription($faker->catchPhrase)
                    ->setUserMargin($faker->randomFloat(2, 0, 0.1))
                    ->setState($state)
                    ->setCustomer($faker->name())
                    ->setCreatedBy($userName);

                // add products
                $products = $faker->products($faker->numberBetween($min, $max)); /* @phpstan-ignore-line */
                foreach ($products as $product) {
                    // copy
                    $item = CalculationItem::create($product)->setQuantity($faker->numberBetween(1, 10));
                    if ($item->isEmptyPrice()) {
                        $item->setPrice($faker->randomFloat(2, 1, 10));
                    }

                    // find category
                    $category = $calculation->findCategory($product->getCategory());

                    // add
                    $category->addItem($item);
                }

                // update
                $service->updateTotal($calculation);

                // save
                if (!$simulate) {
                    $manager->persist($calculation);
                } else {
                    $calculation->setId($id++);
                }

                // add
                $calculations[] = $calculation;
            }
            if (!$simulate) {
                $manager->flush();
            }

            // save
            $this->setSessionValue('admin.generate.simulate', $simulate);

            // serialize
            $items = \array_map(function (Calculation $c) {
                return [
                    'id' => FormatUtils::formatId((int) $c->getId()),
                    'date' => FormatUtils::formatDate($c->getDate()),
                    'state' => $c->getStateCode(),
                    'description' => $c->getDescription(),
                    'customer' => $c->getCustomer(),
                    'margin' => FormatUtils::formatPercent($c->getOverallMargin()),
                    'total' => FormatUtils::formatAmount($c->getOverallTotal()),
                    'color' => $c->getStateColor(),
                ];
            }, $calculations);

            $data = [
                    'result' => true,
                    'count' => $count,
                    'items' => $items,
                    'message' => $this->trans('counters.calculations_generate', ['count' => $count]),
                ];

            return $this->json($data);
        } catch (\Exception $e) {
            $message = $this->trans('generate.error.failed');
            $context = Utils::getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }

    /**
     * Create one or more customers with random data.
     *
     * @Route("/customer", name="generate_customer")
     */
    public function generateCustomer(Request $request, EntityManagerInterface $manager, FakerService $fakerService, LoggerInterface $logger): JsonResponse
    {
        try {
            $customers = [];
            $faker = $fakerService->getFaker();
            $count = $this->getRequestInt($request, 'count');
            $simulate = $this->getRequestBoolean($request, 'simulate', true);

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
                if (!$simulate) {
                    $manager->persist($customer);
                }

                // add
                $customers[] = $customer;
            }
            if (!$simulate) {
                $manager->flush();
            }

            // save
            $this->setSessionValue('admin.generate.simulate', $simulate);

            // serialize
            $items = \array_map(function (Customer $c) {
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
                    'items' => $items,
                    'message' => $this->trans('counters.customers_generate', ['count' => $count]),
                ];

            return $this->json($data);
        } catch (\Exception $e) {
            $message = $this->trans('generate.error.failed');
            $context = Utils::getExceptionContext($e);
            $logger->error($message, $context);

            return $this->jsonException($e, $message);
        }
    }
}
