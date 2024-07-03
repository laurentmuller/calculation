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
use App\Form\Calculation\CalculationGroupType;
use App\Form\Calculation\CalculationType;
use App\Form\CalculationState\CalculationStateListType;
use App\Repository\CategoryRepository;
use App\Repository\GroupRepository;
use App\Tests\Form\CalculationStateTrait;
use App\Tests\Form\EntityTypeTestCase;
use App\Utils\DateUtils;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\PreloadedExtension;

/**
 * @extends EntityTypeTestCase<Calculation, CalculationType>
 */
#[CoversClass(CalculationType::class)]
class CalculationTypeTest extends EntityTypeTestCase
{
    use CalculationStateTrait;

    protected function getData(): array
    {
        return [
            'date' => DateUtils::removeTime(),
            'customer' => 'customer',
            'description' => 'description',
            'userMargin' => 0.0,
            'state' => null,
            'groups' => new ArrayCollection(),
        ];
    }

    protected function getEntityClass(): string
    {
        return Calculation::class;
    }

    /**
     * @throws Exception|\ReflectionException
     */
    protected function getExtensions(): array
    {
        /** @psalm-var array $extensions */
        $extensions = parent::getExtensions();
        $types = [
            new EntityType($this->getRegistry()),
            new CalculationStateListType($this->createMockTranslator()),
            new CalculationGroupType($this->createMock(GroupRepository::class)),
            new CalculationCategoryType($this->createMock(CategoryRepository::class)),
        ];
        $extensions[] = new PreloadedExtension($types, []);

        return $extensions;
    }

    protected function getFormTypeClass(): string
    {
        return CalculationType::class;
    }
}
