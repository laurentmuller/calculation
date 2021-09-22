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

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Model\ProductUpdateQuery;
use App\Model\ProductUpdateResult;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Traits\LoggerTrait;
use App\Traits\MathTrait;
use App\Traits\SessionTrait;
use App\Traits\TranslatorTrait;
use App\Util\FormatUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update the price of products.
 *
 * @author Laurent Muller
 */
class ProductUpdater
{
    use LoggerTrait;
    use MathTrait;
    use SessionTrait;
    use TranslatorTrait;

    private FormFactoryInterface $factory;
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(
        EntityManagerInterface $manager,
        FormFactoryInterface $factory,
        LoggerInterface $logger,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
        $this->manager = $manager;
        $this->factory = $factory;

        // traits
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * Creates the edit form.
     */
    public function createForm(ProductUpdateQuery $query): FormInterface
    {
        // create helper
        $builder = $this->factory->createBuilder(FormType::class, $query);
        $helper = new FormHelper($builder, 'product.update.');

        // add fields
        $helper->field('category')
            ->label('product.fields.category')
            ->updateOption('query_builder', static function (CategoryRepository $repository): QueryBuilder {
                return $repository->getQueryBuilderByGroup(CategoryRepository::FILTER_PRODUCTS);
            })
            ->add(CategoryListType::class);

        $helper->field('allProducts')
            ->notRequired()
            ->rowClass('mb-0')
            ->updateAttribute('data-error', $this->trans('product.update.products_error'))
            ->addCheckboxType();

        $helper->field('products')
            ->label('product.list.title')
            ->updateOption('multiple', true)
            ->updateOption('expanded', true)
            ->updateOption('class', Product::class)
            ->updateOption('choices', $this->getAllProducts())
            ->updateOption('choice_label', 'description')
            ->updateOption('choice_attr', static function (Product $product) {
                return [
                    'price' => $product->getPrice(),
                    'category' => $product->getCategoryId(),
                ];
            })
            ->add(EntityType::class);

        $helper->field('percent')
            ->updateAttribute('data-type', ProductUpdateQuery::UPDATE_PERCENT)
            ->help('product.update.percent_help')
            ->addPercentType();

        $helper->field('fixed')
            ->updateAttribute('data-type', ProductUpdateQuery::UPDATE_FIXED)
            ->help('product.update.fixed_help')
            ->addMoneyType();

        $helper->field('round')
            ->notRequired()
            ->help('product.update.round_help')
            ->helpClass('ml-4')
            ->addCheckboxType();

        $helper->field('simulated')
            ->help('product.update.simulated_help')
            ->helpClass('ml-4')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->updateAttribute('data-error', $this->trans('generate.error.confirm'))
            ->updateAttribute('disabled', $query->isSimulated() ? 'disabled' : null)
            ->notMapped()
            ->addCheckboxType();

        $helper->field('type')
            ->addHiddenType();

        return $helper->createForm();
    }

    /**
     * Create the update query from session.
     */
    public function createUpdateQuery(): ProductUpdateQuery
    {
        $id = $this->getSessionInt('product.update.category', 0);
        $category = $this->getCategory($id);

        $query = new ProductUpdateQuery();
        $query->setAllProducts(true)
            ->setCategory($category)
            ->setProducts($this->getProducts($category))
            ->setPercent($this->getSessionFloat('product.update.percent', 0))
            ->setFixed($this->getSessionFloat('product.update.fixed', 0))
            ->setType($this->getSessionString('product.update.type', ProductUpdateQuery::UPDATE_PERCENT))
            ->setRound($this->isSessionBool('product.update.round', false))
            ->setSimulated($this->isSessionBool('product.update.simulated', true));

        return $query;
    }

    /**
     *  Save the update request to session.
     */
    public function saveUpdateQuery(ProductUpdateQuery $query): void
    {
        $this->setSessionValue('product.update.category', $query->getCategoryId());
        $this->setSessionValue('product.update.simulated', $query->isSimulated());
        $this->setSessionValue('product.update.round', $query->isRound());
        if ($query->isPercent()) {
            $this->setSessionValue('product.update.percent', $query->getValue());
            $this->setSessionValue('product.update.type', ProductUpdateQuery::UPDATE_PERCENT);
        } else {
            $this->setSessionValue('product.update.fixed', $query->getValue());
            $this->setSessionValue('product.update.type', ProductUpdateQuery::UPDATE_FIXED);
        }
    }

    /**
     * Update the products.
     */
    public function update(ProductUpdateQuery $query): ProductUpdateResult
    {
        $result = new ProductUpdateResult();

        if ($query->isAllProducts()) {
            $products = $this->getProducts($query->getCategory());
        } else {
            $products = $query->getProducts();
        }
        if (empty($products)) {
            return $result;
        }

        /** @var Product $product */
        foreach ($products as $product) {
            $oldPrice = $product->getPrice();
            $newPrice = $this->computePrice($oldPrice, $query);
            $product->setPrice($newPrice);

            $result->addProduct([
                'description' => $product->getDescription(),
                'oldPrice' => $oldPrice,
                'newPrice' => $newPrice,
            ]);
        }

        if (!$query->isSimulated() && $result->isValid()) {
            // save
            $this->manager->flush();

            // log results
            $this->logResult($query, $result);
        }

        return $result;
    }

    /**
     * Compute the new product price.
     */
    private function computePrice(float $oldPrice, ProductUpdateQuery $query): float
    {
        if ($query->isPercent()) {
            $newPrice = $oldPrice * (1 + $query->getValue());
        } else {
            $newPrice = $oldPrice + $query->getValue();
        }
        if ($query->isRound()) {
            $newPrice = \round($newPrice * 20, 0) / 20;
        }

        return $this->round($newPrice);
    }

    /**
     * Gets all products with a not empty price.
     *
     * @return Product[] the products
     */
    private function getAllProducts(): array
    {
        /** @var ProductRepository $repository */
        $repository = $this->manager->getRepository(Product::class);

        return $repository->createDefaultQueryBuilder('e')
            ->orderBy('e.description')
            ->where('e.price != 0')
            ->getQuery()
            ->getResult();
    }

    /**
     * Gets the category for the given identifier.
     *
     * @param int $id the category identifier to find
     */
    private function getCategory(int $id): ?Category
    {
        if (0 !== $id) {
            return $this->manager->getRepository(Category::class)->find($id);
        }

        return null;
    }

    /**
     * Gets products for the given category.
     *
     * @return Product[] the products
     */
    private function getProducts(?Category $category): array
    {
        if (null !== $category) {
            /** @var ProductRepository $repository */
            $repository = $this->manager->getRepository(Product::class);

            return $repository->findByCategory($category);
        }

        return [];
    }

    /**
     * Log results.
     */
    private function logResult(ProductUpdateQuery $query, ProductUpdateResult $result): void
    {
        $context = [
            $this->trans('product.fields.category') => $query->getCategoryCode(),
            $this->trans('product.result.updated') => $this->trans('counters.products', ['count' => $result->count()]),
            $this->trans('product.result.value') => $query->isPercent() ? FormatUtils::formatPercent($query->getValue()) : FormatUtils::formatAmount($query->getValue()),
        ];
        $message = $this->trans('product.update.title');
        $this->logInfo($message, $context);
    }
}
