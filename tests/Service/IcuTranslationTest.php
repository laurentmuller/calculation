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

namespace App\Tests\Service;

use App\Tests\KernelServiceTestCase;
use PHPUnit\Framework\Attributes\CoversNothing;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class to test ICU translations.
 */
#[CoversNothing]
final class IcuTranslationTest extends KernelServiceTestCase
{
    private TranslatorInterface $translator;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = $this->getService(TranslatorInterface::class);
    }

    public function testCalculations(): void
    {
        $this->validateTrans('Aucune calculation', 'counters.calculations', ['count' => 0]);
        $this->validateTrans('1 calculation', 'counters.calculations', ['count' => 1]);
        $this->validateTrans('2 calculations', 'counters.calculations', ['count' => 2]);
    }

    public function testCalculationsDay(): void
    {
        $this->validateTrans(
            'Aucune calculation à afficher pour le 29/11/1962',
            'counters.calculations_day',
            ['count' => 0, 'date' => '29/11/1962']
        );
        $this->validateTrans(
            'Afficher la calculation pour le 29/11/1962',
            'counters.calculations_day',
            ['count' => 1, 'date' => '29/11/1962']
        );
        $this->validateTrans(
            'Afficher les 2 calculations pour le 29/11/1962',
            'counters.calculations_day',
            ['count' => 2, 'date' => '29/11/1962']
        );
    }

    public function testCalculationsLower(): void
    {
        $this->validateTrans('aucune calculation', 'counters.calculations_lower', ['count' => 0]);
        $this->validateTrans('1 calculation', 'counters.calculations_lower', ['count' => 1]);
        $this->validateTrans('2 calculations', 'counters.calculations_lower', ['count' => 2]);
    }

    private function validateTrans(string $expected, string $id, array $parameters = []): void
    {
        $actual = $this->translator->trans($id, $parameters);
        self::assertSame($expected, $actual);
    }
}
