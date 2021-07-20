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
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
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
    public function __construct(EntityManagerInterface $manager,
        FormFactoryInterface $factory,
        LoggerInterface $logger,
        RequestStack $requestStack,
        TranslatorInterface $translator)
    {
        $this->manager = $manager;
        $this->factory = $factory;

        // traits
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
    }

    /**
     * Creates the form helper for update products.
     */
    public function createHelper(): FormHelper
    {
        // get values from session
        $category = $this->getCategory($this->getSessionInt('product.update.category', 0));
        $data = [
            'category' => $category,
            'percent' => $this->getSessionFloat('product.update.percent', 0),
            'fixed' => $this->getSessionFloat('product.update.fixed', 0),
            'type' => $this->getSessionString('product.update.type', self::UPDATE_PERCENT),
            'round' => $this->isSessionBool('product.update.round', false),
            'simulated' => $this->isSessionBool('product.update.simulated', true),
        ];

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

        return $helper;
    }

    /**
     * Gets all products.
     *
     * @return Product[] the products
     */
    public function getAllProducts(): array
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
     * Update products.
     *
     * @param Category $category  the category to get products for
     * @param float    $value     the value to multiply by (percent) or to add (fixed amount)
     * @param bool     $percent   true if the value is a percent, false if is a fixed amount
     * @param bool     $round     true to round up price to the nearest 0.05
     * @param bool     $simulated true to simulate the update, false to save changes to the database
     *
     * @return array the result of the update
     */
    public function update(Category $category, float $value, bool $percent, bool $round, bool $simulated): array
    {
        $results = [
            'result' => false,
            'category' => $category,
            'simulated' => $simulated,
            'percent' => $percent,
            'value' => $value,
            'round' => $round,
        ];

        /** @var Product[] $products */
        $products = $this->getProducts($category);
        if (empty($products)) {
            return $results;
        }

        if ($percent) {
            $value += 1.0; // add 100%
        }

        // compute price
        foreach ($products as $product) {
            $oldPrice = $product->getPrice();
            if ($percent) {
                $newPrice = $oldPrice * $value;
            } else {
                $newPrice = $oldPrice + $value;
            }
            if ($round) {
                $newPrice = \round($newPrice * 20, 0) / 20;
            }
            $newPrice = $this->round($newPrice);

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
            $context = [
                $this->trans('product.fields.category') => $category->getCode(),
                $this->trans('product.result.updated') => $this->trans('counters.products', ['count' => \count($products)]),
                $this->trans('product.result.value') => $percent ? FormatUtils::formatPercent($value - 1.0) : FormatUtils::formatAmount($value),
            ];
            $message = $this->trans('product.update.title');
            $this->logInfo($message, $context);
        }

        // save values to session
        $this->setSessionValue('product.update.category', $category->getId());
        $this->setSessionValue('product.update.simulated', $simulated);
        $this->setSessionValue('product.update.round', $round);
        if ($percent) {
            $this->setSessionValue('product.update.percent', $value - 1.0);
            $this->setSessionValue('product.update.type', self::UPDATE_PERCENT);
        } else {
            $this->setSessionValue('product.update.fixed', $value);
            $this->setSessionValue('product.update.type', self::UPDATE_FIXED);
        }

        $results['result'] = true;

        return $results;
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
    private function getProducts(Category $category): array
    {
        /** @var ProductRepository $repository */
        $repository = $this->manager->getRepository(Product::class);

        return $repository->findByCategory($category);
    }
}
