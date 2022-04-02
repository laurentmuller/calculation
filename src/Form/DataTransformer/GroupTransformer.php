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

    /**
     * Constructor.
     */
    public function __construct(private GroupRepository $repository, TranslatorInterface $translator)
    {
        $this->setTranslator($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value): ?Group
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
            $message = $this->trans('group.id_not_found', ['%id%' => $value], 'validators');
            throw new TransformationFailedException($message);
        }

        return $group;
    }

    /**
     * {@inheritdoc}
     *
     * @param Group|null $value
     */
    public function transform($value): ?int
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
