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

namespace App\Form\DataTransformer;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformer to convert a category to an identifier.
 *
 * @author Laurent Muller
 */
class CategoryTransformer implements DataTransformerInterface
{
    /**
     * @var CategoryRepository
     */
    private $repository;

    /**
     * Constructor.
     *
     * @param CategoryRepository $repository the repository to find category
     */
    public function __construct(CategoryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?int $id
     */
    public function reverseTransform($id): ?Category
    {
        if (!$id) {
            return null;
        }

        $category = $this->repository->find((int) $id);
        if (null === $category) {
            throw new TransformationFailedException(\sprintf('An category with the number "%s" does not exist!', $id));
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?Category $category
     */
    public function transform($category)
    {
        if (null !== $category) {
            return $category->getId();
        }

        return 0;
    }
}
