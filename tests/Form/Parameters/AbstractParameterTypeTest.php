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

namespace App\Tests\Form\Parameters;

use App\Enums\EntityAction;
use App\Form\Parameters\AbstractHelperParametersType;
use App\Tests\Fixture\FixtureParameter;
use App\Tests\Fixture\FixtureParametersType;
use Symfony\Component\Form\Test\TypeTestCase;

class AbstractParameterTypeTest extends TypeTestCase
{
    public function testWithAllOptions(): void
    {
        $values = [
            'action' => EntityAction::SHOW,
            'minMargin' => 1.1,
            'text' => 'Fake text',
            'value' => true,
        ];
        $this->validateParameters($values);
    }

    public function testWithInvalidOption(): void
    {
        $values = [
            'fake' => EntityAction::SHOW,
        ];
        $this->validateParameters($values);
    }

    public function testWithoutOptions(): void
    {
        $this->validateParameters();
    }

    private function validateParameters(array $values = []): void
    {
        $options = [];
        if ([] !== $values) {
            $options = [AbstractHelperParametersType::DEFAULT_VALUES => [
                FixtureParameter::getCacheKey() => $values,
            ]];
        }
        $view = $this->factory->create(FixtureParametersType::class, null, $options)
            ->createView();
        $children = $view->children;
        self::assertArrayHasKey('parameter', $children);
    }
}
