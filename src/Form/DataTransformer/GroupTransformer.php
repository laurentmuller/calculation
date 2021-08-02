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
use App\Traits\TranslatorTrait;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Transformer to convert a group to an identifier.
 *
 * @author Laurent Muller
 */
class GroupTransformer implements DataTransformerInterface
{
    use TranslatorTrait;

    private GroupRepository $repository;

    /**
     * Constructor.
     *
     * @param GroupRepository     $repository the repository to find group
     * @param TranslatorInterface $translator the translator used for error messages
     */
    public function __construct(GroupRepository $repository, TranslatorInterface $translator)
    {
        $this->repository = $repository;
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     *
     * @param int|null $id
     */
    public function reverseTransform($id): ?Group
    {
        if (empty($id)) {
            return null;
        }

        $group = $this->repository->find((int) $id);
        if (null === $group) {
            $message = $this->trans('group.id_not_found', ['%id%' => $id], 'validators');
            throw new TransformationFailedException($message);
        }

        return $group;
    }

    /**
     * {@inheritdoc}
     *
     * @param Group|null $group
     */
    public function transform($group): ?int
    {
        if (null !== $group) {
            return $group->getId();
        }

        return null;
    }
}
