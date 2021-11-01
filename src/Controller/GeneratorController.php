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

use App\Generator\CalculationGenerator;
use App\Generator\CustomerGenerator;
use App\Generator\ProductGenerator;
use App\Interfaces\GeneratorInterface;
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
    private const ENTITY_CALCULATION = 'calculation.name';
    private const ENTITY_CUSTOMER = 'customer.name';
    private const ENTITY_PRODUCT = 'product.name';

    private const KEY_COUNT = 'admin.generate.count';
    private const KEY_ENTITY = 'admin.generate.entity';
    private const KEY_SIMULATE = 'admin.generate.simulate';

    /**
     * @Route("", name="generate")
     */
    public function generate(): Response
    {
        $data = [
            'count' => $this->getSessionInt(self::KEY_COUNT, 1),
            'entity' => $this->getSessionString(self::KEY_ENTITY, self::ENTITY_CUSTOMER),
            'simulate' => $this->isSessionBool(self::KEY_SIMULATE, true),
        ];
        $helper = $this->createFormHelper('generate.fields.', $data);

        $helper->field('entity')
            ->updateOption('choice_attr', function (string $choice, string $key): array {
                return ['data-key' => $key];
            })->addChoiceType([
                self::ENTITY_CUSTOMER => $this->generateUrl('generate_customer'),
                self::ENTITY_CALCULATION => $this->generateUrl('generate_calculation'),
                self::ENTITY_PRODUCT => $this->generateUrl('generate_product'),
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
            ->updateAttribute('disabled', $data['simulate'] ? 'disabled' : null)
            ->addCheckboxType();

        return $this->renderForm('admin/generate.html.twig', [
            'form' => $helper->createForm(),
        ]);
    }

    /**
     * Create one or more calculations with random data.
     *
     * @Route("/calculation", name="generate_calculation")
     */
    public function generateCalculations(Request $request, CalculationGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator, self::ENTITY_CALCULATION);
    }

    /**
     * Create one or more customers with random data.
     *
     * @Route("/customer", name="generate_customer")
     */
    public function generateCustomers(Request $request, CustomerGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator, self::ENTITY_CUSTOMER);
    }

    /**
     * Create one or more products with random data.
     *
     * @Route("/product", name="generate_product")
     */
    public function generateProducts(Request $request, ProductGenerator $generator): JsonResponse
    {
        return $this->generateEntities($request, $generator, self::ENTITY_PRODUCT);
    }

    /**
     * Generate entities.
     */
    private function generateEntities(Request $request, GeneratorInterface $generator, string $entity): JsonResponse
    {
        $count = $this->getRequestInt($request, 'count');
        $simulate = $this->getRequestBoolean($request, 'simulate', true);

        $this->setSessionValues([
            self::KEY_COUNT => $count,
            self::KEY_ENTITY => $entity,
            self::KEY_SIMULATE => $simulate,
        ]);

        return $generator->generate($count, $simulate);
    }
}
