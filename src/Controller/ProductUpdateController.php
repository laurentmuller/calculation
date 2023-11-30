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

use App\Entity\Product;
use App\Form\Category\CategoryListType;
use App\Interfaces\RoleInterface;
use App\Model\ProductUpdateQuery;
use App\Repository\CategoryRepository;
use App\Service\ProductUpdateService;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller to update product prices.
 */
#[AsController]
#[Route(path: '/admin')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class ProductUpdateController extends AbstractController
{
    #[Route(path: '/product', name: 'admin_product', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function invoke(Request $request, ProductUpdateService $service): Response
    {
        $query = $service->createQuery();
        $application = $this->getApplication();
        $form = $this->createQueryForm($service, $query);
        if ($this->handleRequestForm($request, $form)) {
            $service->saveQuery($query);
            $result = $service->update($query);
            if (!$query->isSimulate() && $result->isValid()) {
                $application->setLastUpdateProducts();
            }

            return $this->render('admin/product_result.html.twig', [
                'query' => $query,
                'result' => $result,
            ]);
        }

        return $this->render('admin/product_query.html.twig', [
            'last_update' => $application->getLastUpdateProducts(),
            'form' => $form,
        ]);
    }

    private function createQueryForm(ProductUpdateService $service, ProductUpdateQuery $query): FormInterface
    {
        $helper = $this->createFormHelper('product.update.', $query);

        $helper->field('category')
            ->label('product.fields.category')
            ->updateOption('query_builder', static fn (CategoryRepository $repository): QueryBuilder => $repository->getQueryBuilderByGroup(CategoryRepository::FILTER_PRODUCTS))
            ->add(CategoryListType::class);

        $helper->field('allProducts')
            ->updateRowAttribute('class', 'form-group mb-0')
            ->updateAttribute('data-error', $this->trans('product.update.products_error'))
            ->addCheckboxType();

        $helper->field('products')
            ->label('product.list.title')
            ->updateOptions([
                'multiple' => true,
                'expanded' => true,
                'class' => Product::class,
                'choice_label' => 'description',
                'choices' => $service->getAllProducts(),
                'choice_attr' => static fn (Product $product): array => [
                    'data-price' => $product->getPrice(),
                    'data-category' => $product->getCategoryId(),
                ],
            ])
            ->add(EntityType::class);

        $helper->field('percent')
            ->updateAttribute('data-type', ProductUpdateQuery::UPDATE_PERCENT)
            ->updateAttribute('aria-label', $this->trans('product.update.percent'))
            ->help('product.update.percent_help')
            ->addPercentType();

        $helper->field('fixed')
            ->updateAttribute('data-type', ProductUpdateQuery::UPDATE_FIXED)
            ->updateAttribute('aria-label', $this->trans('product.update.fixed'))
            ->help('product.update.fixed_help')
            ->addNumberType();

        $helper->field('round')
            ->help('product.update.round_help')
            ->helpClass('ms-4')
            ->addCheckboxType();

        $helper->addSimulateAndConfirmType($this->getTranslator(), $query->isSimulate());

        $helper->field('type')
            ->addHiddenType();

        return $helper->createForm();
    }
}
