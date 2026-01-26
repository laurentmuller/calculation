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
use App\Model\ProductUpdateQuery;
use App\Model\ProductUpdateResult;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Traits\LoggerAwareTrait;
use App\Traits\MathTrait;
use App\Traits\SessionAwareTrait;
use App\Traits\TranslatorAwareTrait;
use App\Utils\DateUtils;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to update the price of products.
 */
class ProductUpdateService implements ServiceSubscriberInterface
{
    use LoggerAwareTrait;
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;
    use TranslatorAwareTrait;

    private const string KEY_CATEGORY = 'product.update.category';
    private const string KEY_FIXED = 'product.update.fixed';
    private const string KEY_PERCENT = 'product.update.percent';
    private const string KEY_ROUND = 'product.update.round';
    private const string KEY_TYPE = 'product.update.type';

    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly CategoryRepository $categoryRepository,
        private readonly SuspendEventListenerService $service,
        private readonly Security $security,
    ) {
    }

    /**
     * Create the update query.
     */
    public function createQuery(): ProductUpdateQuery
    {
        $category = $this->getCategory();
        $products = $this->getProducts($category);
        /** @phpstan-var ProductUpdateQuery::UPDATE_* $type  $type */
        $type = $this->getSessionString(self::KEY_TYPE, ProductUpdateQuery::UPDATE_PERCENT);

        $query = new ProductUpdateQuery();
        $query->setAllProducts(true)
            ->setPercent($this->getSessionFloat(self::KEY_PERCENT))
            ->setFixed($this->getSessionFloat(self::KEY_FIXED))
            ->setRound($this->isSessionBool(self::KEY_ROUND))
            ->setProducts($products)
            ->setCategory($category)
            ->setType($type);

        return $query;
    }

    /**
     * Gets all products ordered by descriptions.
     *
     * @return Product[] the products
     */
    public function getAllProducts(): array
    {
        return $this->productRepository->findByDescription();
    }

    /**
     *  Save the update query to the session.
     */
    public function saveQuery(ProductUpdateQuery $query): void
    {
        $type = $query->getType();
        $key = $query->isPercent() ? self::KEY_PERCENT : self::KEY_FIXED;
        $this->setSessionValues([
            self::KEY_CATEGORY => $query->getCategoryId(),
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
        if ([] === $products) {
            return $result;
        }

        $percent = $query->isPercent();
        $value = $query->getValue();
        $round = $query->isRound();
        $date = DateUtils::createDatePoint();
        $user = $this->getUser();

        foreach ($products as $product) {
            $oldPrice = $product->getPrice();
            $newPrice = $this->computePrice($oldPrice, $percent, $value, $round);
            if ($oldPrice !== $newPrice) {
                $product->setPrice($newPrice)
                    ->updateTimestampable($date, $user);
                $result->addProduct([
                    'description' => $product->getDescription(),
                    'oldPrice' => $oldPrice,
                    'newPrice' => $newPrice,
                    'delta' => $newPrice - $oldPrice,
                ]);
            }
        }

        if ($query->isSimulate() || !$result->isValid()) {
            return $result;
        }

        $this->service->suspendListeners($this->productRepository->flush(...));
        $this->logResult($query, $result);

        return $result;
    }

    /**
     * Compute the new product price.
     */
    private function computePrice(float $oldPrice, bool $percent, float $value, bool $round): float
    {
        $newPrice = $percent ? $oldPrice * (1.0 + $value) : $oldPrice + $value;
        if ($round) {
            $newPrice = \round($newPrice * 20.0) / 20.0;
        }

        return $this->round($newPrice);
    }

    /**
     * Gets the category.
     */
    private function getCategory(): ?Category
    {
        $id = $this->getSessionInt(self::KEY_CATEGORY);
        if (0 !== $id) {
            return $this->categoryRepository->find($id);
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
        if ($category instanceof Category) {
            return $this->productRepository->findByCategory($category);
        }

        return [];
    }

    /**
     * Gets the current user identifier.
     */
    private function getUser(): string
    {
        return $this->security->getUser()?->getUserIdentifier() ?? $this->trans('common.empty_user');
    }

    /**
     * Log result.
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
