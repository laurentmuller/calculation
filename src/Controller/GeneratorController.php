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

use App\Attribute\Get;
use App\Attribute\GetPost;
use App\Generator\CalculationGenerator;
use App\Generator\CustomerGenerator;
use App\Generator\ProductGenerator;
use App\Interfaces\GeneratorInterface;
use App\Interfaces\RoleInterface;
use App\Service\SuspendEventListenerService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to generate entities.
 */
#[AsController]
#[Route(path: '/generate', name: self::PREFIX)]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class GeneratorController extends AbstractController
{
    private const KEY_COUNT = 'generate.count';
    private const KEY_ENTITY = 'generate.entity';
    private const KEY_SIMULATE = 'generate.simulate';

    private const PREFIX = 'generate';
    private const ROUTE_CALCULATION = '_calculation';
    private const ROUTE_CUSTOMER = '_customer';
    private const ROUTE_PRODUCT = '_product';

    #[GetPost(path: self::INDEX_PATH, name: '')]
    public function generate(): Response
    {
        $data = $this->getSessionData();
        $form = $this->createGenerateForm($data);

        return $this->render('admin/generate.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Create one or more calculations with random data.
     */
    #[Get(path: '/calculation', name: self::ROUTE_CALCULATION)]
    public function generateCalculations(
        Request $request,
        CalculationGenerator $generator,
        SuspendEventListenerService $service
    ): JsonResponse {
        return $this->generateEntities($request, $generator, $service);
    }

    /**
     * Create one or more customers with random data.
     */
    #[Get(path: '/customer', name: self::ROUTE_CUSTOMER)]
    public function generateCustomers(
        Request $request,
        CustomerGenerator $generator,
        SuspendEventListenerService $service
    ): JsonResponse {
        return $this->generateEntities($request, $generator, $service);
    }

    /**
     * Create one or more products with random data.
     */
    #[Get(path: '/product', name: self::ROUTE_PRODUCT)]
    public function generateProducts(
        Request $request,
        ProductGenerator $generator,
        SuspendEventListenerService $service
    ): JsonResponse {
        return $this->generateEntities($request, $generator, $service);
    }

    /**
     * @param array{entity: ?string, count: int, simulate: bool} $data
     *
     * @return FormInterface<mixed>
     */
    private function createGenerateForm(array $data): FormInterface
    {
        $choices = $this->getChoices();
        $attributes = $this->getAttributes($choices);

        $helper = $this->createFormHelper('generate.fields.', $data);
        $helper->field('entity')
            ->updateOption('choice_attr', $attributes)
            ->addChoiceType($choices);
        $helper->field('count')
            ->updateAttributes([
                'min' => 1,
                'max' => 20,
                'step' => 1,
            ])->addNumberType(0);
        $helper->addSimulateAndConfirmType($this->getTranslator(), $data['simulate']);

        return $helper->createForm();
    }

    /**
     * Generate entities.
     */
    private function generateEntities(
        Request $request,
        GeneratorInterface $generator,
        SuspendEventListenerService $service
    ): JsonResponse {
        $count = $this->getRequestInt($request, 'count', 1);
        $simulate = $this->getRequestBoolean($request, 'simulate', true);
        $route = $this->getRequestString($request, '_route', $this->getRoute(self::ROUTE_CUSTOMER));
        $entity = $this->generateUrl($route);

        $this->setSessionValues([
            self::KEY_ENTITY => $entity,
            self::KEY_COUNT => $count,
            self::KEY_SIMULATE => $simulate,
        ]);

        if ($simulate) {
            return $generator->generate($count, true);
        }

        return $service->suspendListeners(fn (): JsonResponse => $generator->generate($count, false));
    }

    /**
     * @param array<string, string> $choices
     */
    private function getAttributes(array $choices): array
    {
        foreach (\array_keys($choices) as $key) {
            $choices[$key] = ['data-key' => \explode('.', $key)[0]];
        }

        return $choices;
    }

    /**
     * @return array<string, string>
     */
    private function getChoices(): array
    {
        return [
            'customer.name' => $this->generateUrl($this->getRoute(self::ROUTE_CUSTOMER)),
            'calculation.name' => $this->generateUrl($this->getRoute(self::ROUTE_CALCULATION)),
            'product.name' => $this->generateUrl($this->getRoute(self::ROUTE_PRODUCT)),
        ];
    }

    private function getRoute(string $suffix): string
    {
        return self::PREFIX . $suffix;
    }

    /**
     * @return array{entity: ?string, count: int, simulate: bool}
     */
    private function getSessionData(): array
    {
        return [
            'entity' => $this->getSessionString(self::KEY_ENTITY),
            'count' => $this->getSessionInt(self::KEY_COUNT, 1),
            'simulate' => $this->isSessionBool(self::KEY_SIMULATE, true),
        ];
    }
}
