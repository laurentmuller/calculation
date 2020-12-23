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

use App\Entity\Group;
use App\Repository\GroupRepository;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Transformer to convert a group to an identifier.
 *
 * @author Laurent Muller
 */
class GroupTransformer implements DataTransformerInterface
{
    /**
     * @var GroupRepository
     */
    private $repository;

    /**
     * Constructor.
     *
     * @param GroupRepository $repository the repository to find group
     */
    public function __construct(GroupRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?int $id
     */
    public function reverseTransform($id): ?Group
    {
        if (!$id) {
            return null;
        }

        $group = $this->repository->find((int) $id);
        if (null === $group) {
            throw new TransformationFailedException(\sprintf('A group with the number "%s" does not exist!', $id));
        }

        return $group;
    }

    /**
     * {@inheritdoc}
     *
     * @param ?Group $group
     */
    public function transform($group)
    {
        if (null !== $group) {
            return $group->getId();
        }

        return 0;
    }
}
