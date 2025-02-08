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

namespace App\Tests\Form\CalculationState;

use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use App\Tests\Entity\IdTrait;
use App\Tests\Form\ManagerRegistryTrait;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @psalm-require-extends TestCase
 */
trait CalculationStateTrait
{
    use IdTrait;
    use ManagerRegistryTrait;

    private ?CalculationState $editableState = null;
    private ?CalculationState $notEditableState = null;

    /**
     * @throws \ReflectionException
     */
    protected function getCalculationStateEntityType(): EntityType
    {
        return new EntityType($this->getCalculationStateRegistry());
    }

    /**
     * @throws \ReflectionException
     */
    protected function getCalculationStateRegistry(): MockObject&ManagerRegistry
    {
        $results = [
            $this->getEditableState(),
            $this->getNotEditableState(),
        ];

        return $this->createManagerRegistry(
            CalculationState::class,
            CalculationStateRepository::class,
            'getQueryBuilderByEditable',
            $results
        );
    }

    /**
     * @throws \ReflectionException
     */
    protected function getEditableState(): CalculationState
    {
        if (!$this->editableState instanceof CalculationState) {
            $this->editableState = new CalculationState();
            $this->editableState->setCode('Editable')
                ->setEditable(true);

            return self::setId($this->editableState, 11);
        }

        return $this->editableState;
    }

    /**
     * @throws \ReflectionException
     */
    protected function getNotEditableState(): CalculationState
    {
        if (!$this->notEditableState instanceof CalculationState) {
            $this->notEditableState = new CalculationState();
            $this->notEditableState->setCode('NotEditable')
                ->setEditable(false);

            return self::setId($this->notEditableState);
        }

        return $this->notEditableState;
    }
}
