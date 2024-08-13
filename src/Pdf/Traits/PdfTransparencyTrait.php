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

namespace App\Pdf\Traits;

use App\Pdf\Enums\PdfBlendMode;

/**
 * Trait to add transparency support.
 *
 * The alpha channel can be from 0.0 (fully transparent) to 1.0 (fully opaque). It applies to all
 * elements (texts, drawings, images).
 *
 * @psalm-type TransparencyStateType = array{
 *      key: int,
 *      object: int,
 *      alpha: float,
 *      blendMode: string}
 *
 * @psalm-require-extends \fpdf\PdfDocument
 */
trait PdfTransparencyTrait
{
    /** @psalm-var TransparencyStateType[] */
    private array $transparencyStates = [];

    /**
     * Reset the alpha mode to default (1.0).
     */
    public function resetAlpha(): void
    {
        $this->setAlpha(1.0);
    }

    /**
     * Set the alpha mode.
     *
     * @param float        $alpha     the alpha channel from 0.0 (fully transparent) to 1.0 (fully opaque)
     * @param PdfBlendMode $blendMode the blend mode
     */
    public function setAlpha(float $alpha, PdfBlendMode $blendMode = PdfBlendMode::NORMAL): void
    {
        $key = \count($this->transparencyStates) + 1;
        $alpha = \max(0.0, \min($alpha, 1.0));
        $state = [
            'key' => $key,
            'object' => 0,
            'alpha' => $alpha,
            'blendMode' => $blendMode->camel(),
        ];
        $this->transparencyStates[] = $state;
        $this->outf('/GS%d gs', $key);
    }

    protected function endDoc(): void
    {
        if ([] !== $this->transparencyStates) {
            $this->updatePdfVersion('1.4');
        }
        parent::endDoc();
    }

    protected function putResourceDictionary(): void
    {
        parent::putResourceDictionary();
        if ([] === $this->transparencyStates) {
            return;
        }
        $this->put('/ExtGState <<');
        foreach ($this->transparencyStates as $state) {
            $this->putf('/GS%d %d 0 R', $state['key'], $state['object']);
        }
        $this->put('>>');
    }

    protected function putResources(): void
    {
        foreach ($this->transparencyStates as &$state) {
            $this->putNewObj();
            $state['object'] = $this->objectNumber;
            $this->put('<</Type /ExtGState');
            $this->putf('/ca %.3F', $state['alpha']);
            $this->putf('/CA %.3F', $state['alpha']);
            $this->putf('/BM /%s', $state['blendMode']);
            $this->put('>>');
            $this->putEndObj();
        }
        parent::putResources();
    }
}
