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
use Doctrine\ORM\Event\OnFlushEventArgs;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to update calculation when groups or items are modified.
 *
 * @internal
 */
final class CalculationListener
{
    /**
     * @var Security
     */
    private $security;

    /**
     * the default user name.
     *
     * @var string
     */
    private $username;

    /**
     * Constructor.
     */
    public function __construct(Security $security, TranslatorInterface $translator)
    {
        $this->security = $security;
        $this->username = $translator->trans('calculation.edit.empty_user');
    }

    /**
     * Handles the flush event.
     */
    public function onFlush(OnFlushEventArgs $args): void
    {
        // get entities
        $em = $args->getEntityManager();
        $unitOfWork = $em->getUnitOfWork();
        $entities = \array_merge($unitOfWork->getScheduledEntityInsertions(), $unitOfWork->getScheduledEntityUpdates(), $unitOfWork->getScheduledCollectionDeletions());
        if (empty($entities)) {
            return;
        }

        // get calculations
        /** @var \App\Entity\Calculation[] $calculations */
        $calculations = $this->getCalculations($entities);
        if (empty($calculations)) {
            return;
        }

        $user = $this->getUserName();
        $date = new \DateTimeImmutable();
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
     * @param array $entities the modified entities
     *
     * @return \App\Entity\Calculation[] the calculations to update (can be empty)
     */
    private function getCalculations(array $entities): array
    {
        $result = [];

        // filter
        foreach ($entities as $entity) {
            // calculation is already updated
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
        if ($user = $this->security->getUser()) {
            return $user->getUsername();
        }

        // default user
        return $this->username;
    }
}
