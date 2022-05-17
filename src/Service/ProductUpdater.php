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

namespace App\Service;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Category\CategoryListType;
use App\Form\FormHelper;
use App\Model\ProductUpdateQuery;
use App\Model\ProductUpdateResult;
use App\Repository\CategoryRepository;
use App\Traits\LoggerTrait;
use App\Traits\MathTrait;
use App\Traits\SessionTrait;
use App\Traits\TranslatorTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerAwareInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to update the price of products.
 */
class ProductUpdater implements LoggerAwareInterface
{
    use LoggerTrait;
    use MathTrait;
    use SessionTrait;
    use TranslatorTrait;

    private const KEY_CATEGORY = 'product.update.category';
    private const KEY_FIXED = 'product.update.fixed';
    private const KEY_PERCENT = 'product.update.percent';
    private const KEY_ROUND = 'product.update.round';
    private const KEY_SIMULATE = 'product.update.simulate';
    private const KEY_TYPE = 'product.update.type';

    /**
     * Constructor.
     */
    public function __construct(
        private readonly EntityManagerInterface $manager,
        private readonly FormFactoryInterface $factory,
        TranslatorInterface $translator,
        RequestStack $requestStack
    ) {
        $this->setTranslator($translator);
        $this->setRequestStack($requestStack);
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
            ->updateOption('query_builder', static fn (CategoryRepository $repository): QueryBuilder => $repository->getQueryBuilderByGroup(CategoryRepository::FILTER_PRODUCTS))
            ->add(CategoryListType::class);

        $helper->field('allProducts')
            ->notRequired()
            ->rowClass('mb-0')
            ->updateAttribute('data-error', $this->trans('product.update.products_error'))
            ->addCheckboxType();

        $helper->field('products')
            ->label('product.list.title')
            ->updateOptions([
                'multiple' => true,
                'expanded' => true,
                'class' => Product::class,
                'choice_label' => 'description',
                'choices' => $this->getAllProducts(),
                'choice_attr' => static function (Product $product) {
                    return [
                        'price' => $product->getPrice(),
                        'category' => $product->getCategoryId(),
                    ];
                },
            ])
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
            ->help('product.update.round_help')
            ->helpClass('ml-4')
            ->notRequired()
            ->addCheckboxType();

        $helper->addCheckboxSimulate()
            ->addCheckboxConfirm($this->translator, $query->isSimulate());

        $helper->field('type')
            ->addHiddenType();

        return $helper->createForm();
    }

    /**
     * Create the update query from session.
     */
    public function createUpdateQuery(): ProductUpdateQuery
    {
        $id = (int) $this->getSessionInt(self::KEY_CATEGORY, 0);
        $category = $this->getCategory($id);

        $query = new ProductUpdateQuery();
        $query->setAllProducts(true)
            ->setCategory($category)
            ->setProducts($this->getProducts($category))
            ->setPercent($this->getSessionFloat(self::KEY_PERCENT, 0))
            ->setFixed($this->getSessionFloat(self::KEY_FIXED, 0))
            ->setType((string) $this->getSessionString(self::KEY_TYPE, ProductUpdateQuery::UPDATE_PERCENT))
            ->setSimulate($this->isSessionBool(self::KEY_SIMULATE, true))
            ->setRound($this->isSessionBool(self::KEY_ROUND));

        return $query;
    }

    /**
     *  Save the update query to session.
     */
    public function saveUpdateQuery(ProductUpdateQuery $query): void
    {
        $percent = $query->isPercent();
        $type = $percent ? ProductUpdateQuery::UPDATE_PERCENT : ProductUpdateQuery::UPDATE_FIXED;
        $key = $percent ? self::KEY_PERCENT : self::KEY_FIXED;

        $this->setSessionValues([
            self::KEY_CATEGORY => $query->getCategoryId(),
            self::KEY_SIMULATE => $query->isSimulate(),
            self::KEY_ROUND => $query->isRound(),
            self::KEY_TYPE => $type,
            $key => $query->getValue(),
        ]);
    }

    /**
     * Update the products.
     */
    public function update(ProductUpdateQuery $query): ProductUpdateResult
    {
        $result = new ProductUpdateResult();

        $products = $query->isAllProducts() ? $this->getProducts($query->getCategory()) : $query->getProducts();
        if (empty($products)) {
            return $result;
        }

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

        if (!$query->isSimulate() && $result->isValid()) {
            $this->manager->flush();
            $this->logResult($query, $result);
        }

        return $result;
    }

    /**
     * Compute the new product price.
     */
    private function computePrice(float $oldPrice, ProductUpdateQuery $query): float
    {
        $newPrice = $query->isPercent() ? $oldPrice * (1 + $query->getValue()) : $oldPrice + $query->getValue();
        if ($query->isRound()) {
            $newPrice = \round($newPrice * 20) / 20;
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
        $repository = $this->manager->getRepository(Product::class);

        /** @var Product[] $products */
        $products = $repository->createDefaultQueryBuilder('e')
            ->orderBy('e.description')
            ->where('e.price != 0')
            ->getQuery()
            ->getResult();

        return $products;
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
     *
     * @psalm-suppress UnnecessaryVarAnnotation
     */
    private function getProducts(?Category $category): array
    {
        if (null !== $category) {
            /** @var \App\Repository\ProductRepository $repository */
            $repository = $this->manager->getRepository(Product::class);

            return $repository->findByCategory($category);
        }

        return [];
    }

    /**
     * Log the update result.
     */
    private function logResult(ProductUpdateQuery $query, ProductUpdateResult $result): void
    {
        $context = [
            $this->trans('product.fields.category') => $query->getCategoryCode(),
            $this->trans('product.result.updated') => $this->trans('counters.products', ['count' => $result->count()]),
            $this->trans('product.result.value') => $query->getFormattedValue(),
        ];
        $message = $this->trans('product.update.title');
        $this->logInfo($message, $context);
    }
}
