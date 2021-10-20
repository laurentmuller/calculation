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
use App\Traits\TranslatorTrait;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Transformer to convert a category to an identifier.
 *
 * @author Laurent Muller
 */
class CategoryTransformer implements DataTransformerInterface
{
    use TranslatorTrait;

    private CategoryRepository $repository;

    /**
     * Constructor.
     */
    public function __construct(CategoryRepository $repository, TranslatorInterface $translator)
    {
        $this->repository = $repository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param int|null $id
     */
    public function reverseTransform($id): ?Category
    {
        if (empty($id)) {
            return null;
        }

        $category = $this->repository->find((int) $id);
        if (!$category instanceof Category) {
            $message = $this->trans('category.id_not_found', ['%id%' => $id], 'validators');
            throw new TransformationFailedException($message);
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     *
     * @param Category|null $category
     */
    public function transform($category): ?int
    {
        if (null !== $category) {
            return $category->getId();
        }

        return null;
    }
}
