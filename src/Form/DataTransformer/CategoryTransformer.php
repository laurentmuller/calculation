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

    /**
     * Constructor.
     */
    public function __construct(private readonly CategoryRepository $repository, TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string|null $value
     */
    public function reverseTransform(mixed $value): ?Category
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
            $message = $this->trans('category.id_not_found', ['%id%' => $value], 'validators');
            throw new TransformationFailedException($message);
        }

        return $category;
    }

    /**
     * {@inheritdoc}
     *
     * @param Category|null $value
     */
    public function transform(mixed $value): ?int
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
