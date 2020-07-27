<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Listener;

use App\Entity\Calculation;
use App\Entity\CalculationGroup;
use App\Entity\CalculationItem;
use App\Traits\TranslatorTrait;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update calculation when groups or items are modified.
 *
 * @internal
 */
final class CalculationListener
{
    use TranslatorTrait;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * Constructor.
     *
     * @param TokenStorageInterface $tokenStorage the token storage to get the user name
     * @param TranslatorInterface   $translator   the translator for messages
     */
    public function __construct(TokenStorageInterface $tokenStorage, TranslatorInterface $translator)
    {
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
    }

    /**
     * Handles the on flush event.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        // get inserted and updated entities
        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $entities = \array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates(), $unitOfWork->getScheduledCollectionDeletions());
        if (empty($entities)) {
            return;
        }

        // filter
        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $this->getCalculations($entities);
        if (empty($calculations)) {
            return;
        }

        $date = new \DateTime();
        $user = $this->getUserName();
        $metadata = $em->getClassMetadata(Calculation::class);
        foreach ($calculations as $calculation) {
            // update
            $calculation->setUpdatedAt($date)
                ->setUpdatedBy($user);
            $em->persist($calculation);

            // recompute
            $unitOfWork->recomputeSingleEntityChangeSet($metadata, $calculation);
        }
    }

    /**
     * Filters the entities and returns the calculations to update.
     *
     * @param array $entities the created and updated entities
     *
     * @return \App\Entity\Calculation[] the calculations to update (can be empty)
     */
    private function getCalculations(array $entities): array
    {
        $result = [];

        // filter
        foreach ($entities as $entity) {
            // calculation are already updated
            if ($entity instanceof Calculation) {
                continue;
            }

            if ($entity instanceof CalculationItem) {
                $entity = $entity->getCalculation();
            } elseif ($entity instanceof CalculationGroup) {
                $entity = $entity->getCalculation();
            }

            // calculation?
            if ($entity instanceof Calculation) {
                $result[(int) $entity->getId()] = $entity;
            }
        }

        return $result;
    }

    /**
     * Gets the user name.
     */
    private function getUserName(): string
    {
        if ($token = $this->tokenStorage->getToken()) {
            $user = $token->getUser();
            if ($user instanceof UserInterface) {
                return $user->getUsername();
            }
            if (\is_string($user)) {
                return $user;
            }
        }

        // default user
        return $this->trans('calculation.edit.empty_user');
    }
}
