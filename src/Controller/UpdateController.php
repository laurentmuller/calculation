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
use Symfony\Component\PropertyAccess\PropertyAccess;
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

        return $this->renderForm('admin/update.html.twig', [
            'form' => $helper->createForm(),
        ]);
    }

    /**
     * Update calculations with random customers.
     *
     * @Route("/calculation", name="update_calculation")
     */
    public function updateCalculation(EntityManagerInterface $manager, FakerService $service, SuspendEventListenerService $listener): RedirectResponse
    {
        $styles = [0, 1, 2];
        $generator = $service->getGenerator();
        $states = $this->getCalculationState($manager);

        /** @var Calculation[] $calculations */
        $calculations = $manager->getRepository(Calculation::class)->findAll();

        try {
            $listener->disableListeners();
            foreach ($calculations as $calculation) {
                $style = (int) $generator->randomElement($styles);
                switch ($style) {
                    case 0:
                        $calculation->setCustomer($generator->company());
                        break;

                    case 1:
                        $calculation->setCustomer($generator->name(Person::GENDER_MALE));
                        break;

                    default:
                        $calculation->setCustomer($generator->name(Person::GENDER_FEMALE));
                        break;
                }

                /** @psalm-var CalculationState $state */
                $state = $generator->randomElement($states);
                $description = $generator->catchPhrase();
                $calculation->setState($state)
                    ->setDescription($description);
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
        $styles = [0, 1, 2];
        $genders = $this->getGenders();
        $accessor = PropertyAccess::createPropertyAccessor();
        $generator = $service->getGenerator();

        /** @var Customer[] $customers */
        $customers = $manager->getRepository(Customer::class)->findAll();

        try {
            $listener->disableListeners();
            foreach ($customers as $customer) {
                $style = (int) $generator->randomElement($styles);
                $gender = (string) $generator->randomElement($genders);

                switch ($style) {
                    case 0: // company
                        $this->replace($accessor, $customer, 'company', $generator->company())
                            ->replace($accessor, $customer, 'title', null)
                            ->replace($accessor, $customer, 'firstName', null)
                            ->replace($accessor, $customer, 'lastName', null)
                            ->replace($accessor, $customer, 'email', $generator->companyEmail());
                        break;
                    case 1: // contact
                        $this->replace($accessor, $customer, 'company', null)
                            ->replace($accessor, $customer, 'title', $generator->title($gender))
                            ->replace($accessor, $customer, 'firstName', $generator->firstName($gender))
                            ->replace($accessor, $customer, 'lastName', $generator->lastName())
                            ->replace($accessor, $customer, 'email', $generator->email());
                        break;
                    default: // both
                        $this->replace($accessor, $customer, 'company', $generator->company())
                            ->replace($accessor, $customer, 'title', $generator->title($gender))
                            ->replace($accessor, $customer, 'firstName', $generator->firstName($gender))
                            ->replace($accessor, $customer, 'lastName', $generator->lastName())
                            ->replace($accessor, $customer, 'email', $generator->email());
                        break;
                }

                // other fields
                $this->replace($accessor, $customer, 'address', $generator->streetAddress())
                    ->replace($accessor, $customer, 'zipCode', $generator->postcode())
                    ->replace($accessor, $customer, 'city', $generator->city());
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
    private function getGenders(): array
    {
        return [Person::GENDER_MALE, Person::GENDER_FEMALE];
    }

    /**
     * Update an element property.
     *
     * @param PropertyAccessor $accessor the property accessor to get or set value
     * @param object|array     $element  the element to update
     * @param string           $property the property name
     * @param mixed            $value    the new value to set
     */
    private function replace(PropertyAccessor $accessor, object|array $element, string $property, mixed $value): self
    {
        $accessor->setValue($element, $property, $value);

        return $this;
    }
}
