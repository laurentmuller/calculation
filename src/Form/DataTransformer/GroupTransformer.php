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

use App\Entity\Group;
use App\Repository\GroupRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformer to convert a group to an identifier.
 *
 * @implements DataTransformerInterface<Group|null, int|null>
 */
class GroupTransformer implements DataTransformerInterface
{
    /**
     * Constructor.
     */
    public function __construct(private readonly GroupRepository $repository)
    {
    }

    /**
     * {@inheritdoc}
     *
     * @param int|string|null $value
     *
     * @return ?Group
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

        $group = $this->repository->find((int) $value);
        if (!$group instanceof Group) {
            $message = \sprintf('Unable to find a group for the value %s.', $value);
            throw new TransformationFailedException($message);
        }

        return $group;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?Group $value
     *
     * @return ?int
     */
    public function transform(mixed $value)
    {
        if (null === $value) {
            return null;
        }

        if (!$value instanceof Group) {
            $message = \sprintf('An group expected, a "%s" given.', \get_debug_type($value));
            throw new TransformationFailedException($message);
        }

        return $value->getId();
    }
}
