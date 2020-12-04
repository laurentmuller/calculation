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
use App\Entity\CalculationState;
use App\Entity\Customer;
use App\Service\FakerService;
use App\Service\SuspendEventListenerService;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Provider\Person;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Controller to update entities.
 *
 * @author Laurent Muller
 *
 * @Route("/update")
 * @IsGranted("ROLE_SUPER_ADMIN")
 */
class UpdateController extends AbstractController
{
    /**
     * @Route("", name="update")
     */
    public function update(): Response
    {
        $type = UrlGeneratorInterface::ABSOLUTE_URL;

        // choices
        $choices = [
            'customer.name' => $this->generateUrl('update_customer', [], $type),
            'calculation.name' => $this->generateUrl('update_calculation', [], $type),
        ];

        // attributes
        $attributes = [
            'customer.name' => ['data-help' => $this->trans('update.help.customer')],
            'calculation.name' => ['data-help' => $this->trans('update.help.calculation')],
        ];

        $helper = $this->createFormHelper('update.fields.');

        $helper->field('entity')
            ->updateOption('choice_attr', $attributes)
            ->help('update.help.customer')
            ->addChoiceType($choices);

        $helper->field('confirm')
            ->notMapped()
            ->updateAttribute('data-error', $this->trans('update.error.confirm'))
            ->addCheckboxType();

        return $this->render('admin/update.html.twig', [
            'form' => $helper->createView(),
        ]);
    }

    /**
     * Update calculations with random customers.
     *
     * @Route("/calculation", name="update_calculation")
     */
    public function updateCalculation(EntityManagerInterface $manager, FakerService $service, SuspendEventListenerService $listener): RedirectResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $manager->getRepository(Calculation::class)->findAll();
        $states = $this->getCalculationState($manager);
        $styles = [0, 1, 2];

        try {
            $listener->disableListeners();
            foreach ($calculations as $calculation) {
                $style = $faker->randomElement($styles);
                switch ($style) {
                    case 0:
                        $calculation->setCustomer($faker->company);
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
        } finally {
            $listener->enableListeners();
        }

        $count = \count($calculations);
        $this->infoTrans('counters.calculations_update', ['count' => $count]);

        return $this->redirectToHomePage();
    }

    /**
     * Update customers with random values.
     *
     * @Route("/customer", name="update_customer")
     */
    public function updateCustomer(EntityManagerInterface $manager, FakerService $service, SuspendEventListenerService $listener): RedirectResponse
    {
        /** @var \Faker\Generator $faker */
        $faker = $service->getFaker();

        /** @var \App\Entity\Customer[] $customers */
        $customers = $manager->getRepository(Customer::class)->findAll();

        $styles = [0, 1, 2];
        $genders = $this->getGenders();
        $accessor = new PropertyAccessor();

        try {
            $listener->disableListeners();
            foreach ($customers as $customer) {
                $style = $faker->randomElement($styles);
                $gender = $faker->randomElement($genders);

                switch ($style) {
                    case 0: // company
                        $this->replace($accessor, $customer, 'company', $faker->company)
                            ->replace($accessor, $customer, 'title', null)
                            ->replace($accessor, $customer, 'firstName', null)
                            ->replace($accessor, $customer, 'lastName', null)
                            ->replace($accessor, $customer, 'email', $faker->companyEmail);
                        break;
                    case 1: // contact
                        $this->replace($accessor, $customer, 'company', null)
                            ->replace($accessor, $customer, 'title', $faker->title($gender))
                            ->replace($accessor, $customer, 'firstName', $faker->firstName($gender))
                            ->replace($accessor, $customer, 'lastName', $faker->lastName)
                            ->replace($accessor, $customer, 'email', $faker->email);
                        break;
                    default: // both
                        $this->replace($accessor, $customer, 'company', $faker->company)
                            ->replace($accessor, $customer, 'title', $faker->title($gender))
                            ->replace($accessor, $customer, 'firstName', $faker->firstName($gender))
                            ->replace($accessor, $customer, 'lastName', $faker->lastName)
                            ->replace($accessor, $customer, 'email', $faker->email);
                        break;
                }

                // other fields
                $this->replace($accessor, $customer, 'address', $faker->streetAddress)
                    ->replace($accessor, $customer, 'zipCode', $faker->postcode)
                    ->replace($accessor, $customer, 'city', $faker->city);
            }
            $manager->flush();
        } finally {
            $listener->enableListeners();
        }

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
     * Gets the genders.
     *
     * @return string[]
     */
    private function getGenders()
    {
        return [Person::GENDER_MALE, Person::GENDER_FEMALE];
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
        $accessor->setValue($element, $property, $value);

        return $this;
    }
}
