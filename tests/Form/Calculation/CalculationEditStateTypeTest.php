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

namespace App\Tests\Form\Calculation;

use App\Entity\Calculation;
use App\Form\Calculation\CalculationCategoryType;
use App\Form\Calculation\CalculationEditStateType;
use App\Form\Calculation\CalculationGroupType;
use App\Form\CalculationState\CalculationStateListType;
use App\Form\Type\PlainType;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Service\ApplicationService;
use App\Tests\Form\CalculationState\CalculationStateTrait;
use App\Tests\Form\EntityTypeTestCase;
use App\Tests\TranslatorMockTrait;
use App\Utils\DateUtils;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

/**
 * @extends EntityTypeTestCase<Calculation, CalculationEditStateType>
 */
final class CalculationEditStateTypeTest extends EntityTypeTestCase
{
    use CalculationStateTrait;
    use TranslatorMockTrait;

    private MockObject&ApplicationService $application;
    private bool $marginBelow = false;

    #[\Override]
    protected function setUp(): void
    {
        $this->application = $this->createMock(ApplicationService::class);
        parent::setUp();
    }

    public function testSubmitValidDataNotMarginBelow(): void
    {
        $this->marginBelow = true;
        $this->submitValidData();
    }

    #[\Override]
    protected function getData(): array
    {
        return [
            'date' => DateUtils::createDate(),
            'state' => null,
        ];
    }

    #[\Override]
    protected function getEntityClass(): string
    {
        return Calculation::class;
    }

    #[\Override]
    protected function getFormTypeClass(): string
    {
        return CalculationEditStateType::class;
    }

    /**
     * @throws \ReflectionException
     */
    #[\Override]
    protected function getPreloadedExtensions(): array
    {
        $translator = $this->createMockTranslator();
        $this->application->method('isMarginBelow')
            ->willReturnCallback(fn (): bool => $this->marginBelow);

        return [
            new PlainType($translator),
            new EntityType($this->getCalculationStateRegistry()),
            new CalculationStateListType($translator),
            new CalculationGroupType($this->createMock(GroupRepository::class)),
            new CalculationCategoryType($this->createMock(CategoryRepository::class)),
            new CalculationEditStateType($this->application, $translator),
        ];
    }
}
