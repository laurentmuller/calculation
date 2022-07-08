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

namespace App\Form\DataTransformer;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformer to convert a category to an identifier.
 *
 * @implements DataTransformerInterface<?Category, ?int>
 */
class CategoryTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     */
    public function __construct(private readonly CategoryRepository $repository)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string|null $value
     *
     * @return ?Category
     */
    public function reverseTransform(mixed $value)
    {
        if (null === $value) {
            return null;
        }

        if (!\is_numeric($value)) {
            $message = \sprintf('A number expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        $category = $this->repository->find((int) $value);
        if (!$category instanceof Category) {
            $message = \sprintf('Unable to find a category for the value "%s".', $value);
            throw new TransformationFailedException($message);
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?Category $value
     *
     * @return ?int
     */
    public function transform(mixed $value)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Category) {
            $message = \sprintf('A category expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        return $value->getId();
    }
}
