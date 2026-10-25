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

namespace App\Tests\Twig;

use App\Entity\CalculationState;
use App\Repository\CalculationStateRepository;
use App\Tests\Entity\IdTrait;
use App\Twig\BorderExtension;
use Symfony\Component\Cache\Adapter\NullAdapter;

/**
 * @extends RuntimeTestCase<BorderExtension>
 */
final class BorderExtensionTest extends RuntimeTestCase
{
    use IdTrait;

    public function testDeleteCache(): void
    {
        self::expectNotToPerformAssertions();
        $this->getService()->deleteCache();
    }

    #[\Override]
    protected function createService(): BorderExtension
    {
        return new BorderExtension(
            $this->createRepository(),
            new NullAdapter()
        );
    }

    #[\Override]
    protected static function getFixturesDirectory(): string
    {
        return __DIR__ . '/Fixtures/BorderExtension';
    }

    private function createRepository(): CalculationStateRepository
    {
        $state = new CalculationState();
        $state->setColor('#000000');
        self::setId($state);

        $repository = self::createStub(CalculationStateRepository::class);
        $repository->method('findAll')
            ->willReturn([$state]);

        return $repository;
    }
}
