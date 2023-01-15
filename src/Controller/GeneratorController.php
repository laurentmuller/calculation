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

use App\Generator\CalculationGenerator;
use App\Generator\CustomerGenerator;
use App\Generator\ProductGenerator;
use App\Interfaces\GeneratorInterface;
use App\Interfaces\RoleInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to generate entities.
 */
#[AsController]
#[Route(path: '/generate')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class GeneratorController extends AbstractController
{
    final public const ROUTE_CALCULATION = 'generate_calculation';
    final public const ROUTE_CUSTOMER = 'generate_customer';
    final public const ROUTE_PRODUCT = 'generate_product';

    private const KEY_COUNT = 'admin.generate.count';
    private const KEY_ENTITY = 'admin.generate.entity';
    private const KEY_SIMULATE = 'admin.generate.simulate';

    /**
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    #[Route(path: '', name: 'generate')]
    public function generate(): Response
    {
        $data = $this->getSessionData();
        $helper = $this->createFormHelper('generate.fields.', $data);

        $choices = $this->getChoices();
        $attributes = $this->getAttributes($choices);
        $helper->field('entity')
            ->updateOption('choice_attr', $attributes)
            ->addChoiceType($choices);

        $helper->field('count')
            ->updateAttributes([
                'min' => 1,
                'max' => 20,
                'step' => 1,
            ])->addNumberType(0);

        $helper->addCheckboxSimulate()
            ->addCheckboxConfirm($this->getTranslator(), $data['simulate']);

        return $this->render('admin/generate.html.twig', [
            'form' => $helper->createForm(),
        ]);
    }

    /**
     * Create one or more calculations with random data.
     */
    #[Route(path: '/calculation', name: self::ROUTE_CALCULATION)]
    public function generateCalculations(Request $request, CalculationGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator);
    }

    /**
     * Create one or more customers with random data.
     */
    #[Route(path: '/customer', name: self::ROUTE_CUSTOMER)]
    public function generateCustomers(Request $request, CustomerGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator);
    }

    /**
     * Create one or more products with random data.
     */
    #[Route(path: '/product', name: self::ROUTE_PRODUCT)]
    public function generateProducts(Request $request, ProductGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator);
    }

    /**
     * Generate entities.
     */
    private function generateEntities(Request $request, GeneratorInterface $generator): JsonResponse
    {
        $count = $this->getRequestInt($request, 'count');
        $simulate = $this->getRequestBoolean($request, 'simulate', true);
        $route = (string) $request->attributes->get('_route', self::ROUTE_CUSTOMER);
        $entity = $this->generateUrl($route);

        $this->setSessionValues([
            self::KEY_COUNT => $count,
            self::KEY_SIMULATE => $simulate,
            self::KEY_ENTITY => $entity,
        ]);

        return $generator->generate($count, $simulate);
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
            'customer.name' => $this->generateUrl(self::ROUTE_CUSTOMER),
            'calculation.name' => $this->generateUrl(self::ROUTE_CALCULATION),
            'product.name' => $this->generateUrl(self::ROUTE_PRODUCT),
        ];
    }

    /**
     * @return array{count: ?int, entity: ?string, simulate: bool}
     */
    private function getSessionData(): array
    {
        return [
            'count' => $this->getSessionInt(self::KEY_COUNT, 1),
            'entity' => $this->getSessionString(self::KEY_ENTITY),
            'simulate' => $this->isSessionBool(self::KEY_SIMULATE, true),
        ];
    }
}
