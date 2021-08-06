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

    /**
     * Update products with a fixed amount.
     */
    public const UPDATE_FIXED = 'fixed';

    /**
     * Update products with a percent.
     */
    public const UPDATE_PERCENT = 'percent';

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
    public function createForm(): FormInterface
    {
        // get values from session
        $data = $this->loadFromSession();

        // create helper
        $builder = $this->factory->createBuilder(FormType::class, $data);
        $helper = new FormHelper($builder, 'product.update.');

        // add fields
        $helper->field('category')
            ->label('product.fields.category')
            ->updateOption('query_builder', function (CategoryRepository $repository): QueryBuilder {
                return $repository->getQueryBuilderByGroup(CategoryRepository::FILTER_PRODUCTS);
            })
            ->add(CategoryListType::class);

        $helper->field('all_products')
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
            ->updateOption('choice_attr', function (Product $product) {
                return [
                    'price' => $product->getPrice(),
                    'category' => $product->getCategoryId(),
                ];
            })
            ->add(EntityType::class);

        $helper->field('percent')
            ->updateAttribute('data-type', self::UPDATE_PERCENT)
            ->help('product.update.percent_help')
            ->addPercentType();

        $helper->field('fixed')
            ->updateAttribute('data-type', self::UPDATE_FIXED)
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
            ->updateAttribute('disabled', $data['simulated'] ? 'disabled' : null)
            ->notMapped()
            ->addCheckboxType();

        $helper->field('type')
            ->addHiddenType();

        return $helper->createForm();
    }

    /**
     * Update the products.
     *
     * @param array $data the form data
     *
     * @return array the result of the update
     */
    public function update(array $data): array
    {
        // get values
        $category = $data['category'];
        $products = $data['products'];
        $round = (bool) $data['round'];
        $simulated = (bool) $data['simulated'];
        $all_products = (bool) $data['all_products'];
        $use_percent = self::UPDATE_PERCENT === $data['type'];
        $value = $use_percent ? (float) $data['percent'] : (float) $data['fixed'];

        $results = [
            'result' => false,
            'category' => $category,
            'simulated' => $simulated,
            'round' => $round,
            'use_percent' => $use_percent,
            'value' => $value,
        ];

        if ($all_products) {
            $products = $this->getProducts($category);
        }
        if (empty($products)) {
            return $results;
        }

        /** @var Product $product */
        foreach ($products as $product) {
            $oldPrice = $product->getPrice();
            $newPrice = $this->computeNewPrice($oldPrice, $value, $use_percent, $round);

            $product->setPrice($newPrice);

            $results['products'][] = [
                'description' => $product->getDescription(),
                'oldPrice' => $oldPrice,
                'newPrice' => $newPrice,
            ];
        }

        if (!$simulated) {
            // save
            $this->manager->flush();

            // log results
            $this->logResults($category, $products, $value, $use_percent);
        }

        // save values to session
        $this->saveToSession($category, $simulated, $round, $value, $use_percent);

        // ok
        $results['result'] = true;

        return $results;
    }

    /**
     * Compute the new product price.
     *
     * @param float $oldPrice    the old price of the product
     * @param float $value       the value to update with
     * @param bool  $use_percent true if the value is a percentage, false if is a fixed amount
     * @param bool  $round       true to round new value up to 0.05
     *
     * @return float the new price
     */
    private function computeNewPrice(float $oldPrice, float $value, bool $use_percent, bool $round): float
    {
        if ($use_percent) {
            $newPrice = $oldPrice * (1 + $value);
        } else {
            $newPrice = $oldPrice + $value;
        }
        if ($round) {
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
     * Load user settings from session.
     */
    private function loadFromSession(): array
    {
        $category = $this->getCategory($this->getSessionInt('product.update.category', 0));

        return [
            'all_products' => true,
            'category' => $category,
            'products' => $this->getProducts($category),
            'percent' => $this->getSessionFloat('product.update.percent', 0),
            'fixed' => $this->getSessionFloat('product.update.fixed', 0),
            'type' => $this->getSessionString('product.update.type', self::UPDATE_PERCENT),
            'round' => $this->isSessionBool('product.update.round', false),
            'simulated' => $this->isSessionBool('product.update.simulated', true),
        ];
    }

    /**
     * Log update results.
     */
    private function logResults(Category $category, array $products, float $value, bool $use_percent): void
    {
        $context = [
            $this->trans('product.fields.category') => $category->getCode(),
            $this->trans('product.result.updated') => $this->trans('counters.products', ['count' => \count($products)]),
            $this->trans('product.result.value') => $use_percent ? FormatUtils::formatPercent($value) : FormatUtils::formatAmount($value),
        ];
        $message = $this->trans('product.update.title');
        $this->logInfo($message, $context);
    }

    /**
     * Save user settings to session.
     */
    private function saveToSession(Category $category, bool $simulated, bool $round, float $value, bool $use_percent): void
    {
        $this->setSessionValue('product.update.category', $category->getId());
        $this->setSessionValue('product.update.simulated', $simulated);
        $this->setSessionValue('product.update.round', $round);
        if ($use_percent) {
            $this->setSessionValue('product.update.percent', $value);
            $this->setSessionValue('product.update.type', self::UPDATE_PERCENT);
        } else {
            $this->setSessionValue('product.update.fixed', $value);
            $this->setSessionValue('product.update.type', self::UPDATE_FIXED);
        }
    }
}
