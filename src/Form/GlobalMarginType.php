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

namespace App\Form;

use App\Entity\GlobalMargin;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Global margin edit type.
 *
 * @author Laurent Muller
 */
class GlobalMarginType extends AbstractMarginType
{
    /**
     * The entity manager to check overlap.
     *
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        parent::__construct(GlobalMargin::class);
        $this->manager = $manager;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        // callback
        $resolver->setDefaults([
            'constraints' => [
                new Callback([
                    'callback' => [$this, 'validate'],
                ]),
            ],
        ]);

        // entity manager
        $resolver->setDefined('manager');
    }

    /**
     * Validation callback.
     *
     * @param GlobalMargin              $data    the entity to validate
     * @param executionContextInterface $context the execution context
     */
    public function validate(GlobalMargin $data, ExecutionContextInterface $context): void
    {
        if (isset($this->manager)) {
            $min = $data->getMinimum();
            $max = $data->getMaximum();
            $margins = $this->manager->getRepository(GlobalMargin::class)->findAll();
            foreach ($margins as $margin) {
                // same?
                if ($margin->getId() === $data->getId()) {
                    continue;
                }

                // check minimum
                if ($min >= $margin->getMinimum() && $min < $margin->getMaximum()) {
                    $context->buildViolation('abstract_margin.minimum_overlap')
                        ->atPath('minimum')
                        ->addViolation();
                    break;
                }

                // check maximum
                if ($max > $margin->getMinimum() && $max < $margin->getMaximum()) {
                    $context->buildViolation('abstract_margin.maximum_overlap')
                        ->atPath('maximum')
                        ->addViolation();
                    break;
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function percent(): bool
    {
        return false;
    }
}
