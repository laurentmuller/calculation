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

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class to test ICU translations.
 */
class IcuTranslationTest extends KernelTestCase
{
    private ?TranslatorInterface $translator = null;

    /**
     * @throws \Exception
     */
    protected function setUp(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        // @phpstan-ignore-next-line
        $this->translator = $container->get(TranslatorInterface::class);
    }

    public function testCalculations(): void
    {
        self::assertNotNull($this->translator);
        self::assertSame('Aucune calculation', $this->translator->trans('counters.calculations', ['count' => 0]));
        self::assertSame('1 calculation', $this->translator->trans('counters.calculations', ['count' => 1]));
        self::assertSame('2 calculations', $this->translator->trans('counters.calculations', ['count' => 2]));
    }

    public function testCalculationsDay(): void
    {
        self::assertNotNull($this->translator);
        self::assertSame('Aucune calculation à afficher pour le 29/11/1962', $this->translator->trans('counters.calculations_day', ['count' => 0, 'date' => '29/11/1962']));
        self::assertSame('Afficher la calculation pour le 29/11/1962', $this->translator->trans('counters.calculations_day', ['count' => 1, 'date' => '29/11/1962']));
        self::assertSame('Afficher les 2 calculations pour le 29/11/1962', $this->translator->trans('counters.calculations_day', ['count' => 2, 'date' => '29/11/1962']));
    }

    public function testCalculationsLower(): void
    {
        self::assertNotNull($this->translator);
        self::assertSame('aucune calculation', $this->translator->trans('counters.calculations_lower', ['count' => 0]));
        self::assertSame('1 calculation', $this->translator->trans('counters.calculations_lower', ['count' => 1]));
        self::assertSame('2 calculations', $this->translator->trans('counters.calculations_lower', ['count' => 2]));
    }
}
