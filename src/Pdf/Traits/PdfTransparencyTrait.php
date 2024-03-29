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
 * The alpha channel can be from 0 (fully transparent) to 1 (fully opaque). It applies to all
 * elements (text, drawings, images).
 *
 * @psalm-type GStateType = array{
 *      key: int,
 *      object: int,
 *      alpha: float,
 *      blend_mode: string}
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 */
trait PdfTransparencyTrait
{
    /** @psalm-var GStateType[] */
    private array $gStates = [];

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
     * @param float        $alpha     the alpha channel from 0 (fully transparent) to 1 (fully opaque)
     * @param PdfBlendMode $blendMode the blend mode
     */
    public function setAlpha(float $alpha, PdfBlendMode $blendMode = PdfBlendMode::NORMAL): void
    {
        $key = \count($this->gStates) + 1;
        $gState = [
            'key' => $key,
            'object' => 0,
            'blend_mode' => $blendMode->camel(),
            'alpha' => $this->validateRange($alpha, 0.0, 1.0),
        ];
        $this->gStates[] = $gState;
        $this->outf('/GS%d gs', $key);
    }

    protected function endDoc(): void
    {
        if ([] !== $this->gStates) {
            $this->updatePdfVersion('1.4');
        }
        parent::endDoc();
    }

    protected function putResourceDictionary(): void
    {
        parent::putResourceDictionary();
        if ([] !== $this->gStates) {
            $this->put('/ExtGState <<');
            foreach ($this->gStates as $gState) {
                $this->putf('/GS%d %d 0 R', $gState['key'], $gState['object']);
            }
            $this->put('>>');
        }
    }

    protected function putResources(): void
    {
        foreach ($this->gStates as &$gState) {
            $this->putNewObj();
            $gState['object'] = $this->objectNumber;
            $this->put('<</Type /ExtGState');
            $this->putf('/ca %.3F', $gState['alpha']);
            $this->putf('/CA %.3F', $gState['alpha']);
            $this->putf('/BM /%s', $gState['blend_mode']);
            $this->put('>>');
            $this->putEndObj();
        }
        parent::putResources();
    }
}
