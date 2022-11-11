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
use App\Traits\LoggerAwareTrait;
use App\Traits\MathTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to update the price of products.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class ProductUpdater implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use MathTrait;
    use ServiceSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const KEY_CATEGORY = 'product.update.category';
    private const KEY_FIXED = 'product.update.fixed';
    private const KEY_PERCENT = 'product.update.percent';
    private const KEY_ROUND = 'product.update.round';
    private const KEY_SIMULATE = 'product.update.simulate';
    private const KEY_TYPE = 'product.update.type';

    /**
     * Constructor.
     */
    public function __construct(private readonly EntityManagerInterface $manager, private readonly FormFactoryInterface $factory)
    {
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
                'choice_attr' => static fn (Product $product): array => [
                    'data-price' => $product->getPrice(),
                    'data-category' => $product->getCategoryId(),
                ],
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
            ->addCheckboxConfirm($this->getTranslator(), $query->isSimulate());

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
        $result->setSimulate($query->isSimulate());

        // get products
        $products = $query->isAllProducts() ? $this->getProducts($query->getCategory()) : $query->getProducts();
        if (empty($products)) {
            return $result;
        }

        // get query values
        $percent = $query->isPercent();
        $value = $query->getValue();
        $round = $query->isRound();

        // update price
        foreach ($products as $product) {
            $oldPrice = $product->getPrice();
            $newPrice = $this->computePrice($oldPrice, $percent, $value, $round);

            if ($oldPrice !== $newPrice) {
                $product->setPrice($newPrice);
                $result->addProduct([
                    'description' => $product->getDescription(),
                    'oldPrice' => $oldPrice,
                    'newPrice' => $newPrice,
                ]);
            }
        }

        // save if applicable
        if (!$query->isSimulate() && $result->isValid()) {
            $this->manager->flush();
            $this->logResult($query, $result);
        }

        return $result;
    }

    /**
     * Compute the new product price.
     */
    private function computePrice(float $oldPrice, bool $percent, float $value, bool $isRound): float
    {
        $newPrice = $percent ? $oldPrice * (1 + $value) : $oldPrice + $value;
        if ($isRound) {
            $newPrice = \round($newPrice * 20) / 20;
        }

        return $this->round($newPrice);
    }

    /**
     * Gets all products ordered by descriptions.
     *
     * @return Product[] the products
     */
    private function getAllProducts(): array
    {
        return $this->manager->getRepository(Product::class)
            ->findAllByDescription();
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
     * Gets products for the given category (if any); an empty array otherwise.
     *
     * @return Product[] the products
     */
    private function getProducts(?Category $category): array
    {
        if (null !== $category) {
            return $this->manager->getRepository(Product::class)
                ->findByCategory($category);
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
