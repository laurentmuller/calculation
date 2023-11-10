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

use App\Traits\MathTrait;

/**
 * Trait to add transparency support.
 *
 * The alpha channel can be from 0 (fully transparent) to 1 (fully opaque). It applies to all
 * elements (text, drawings, images).
 *
 * @psalm-type ParameterType = array{
 *     stroking: float,
 *     blend_mode: string}
 * @psalm-type GsStateType = array{
 *     index: int,
 *     parameters: ParameterType}
 *
 * @psalm-require-extends \App\Pdf\PdfDocument
 */
trait PdfTransparencyTrait
{
    use MathTrait;

    /** @psalm-var array<int, GsStateType> */
    private array $gsStates = [];

    /**
     * Reset the alpha mode to default (1.0).
     */
    public function resetAlpha(): static
    {
        return $this->setAlpha(1.0);
    }

    /**
     * Set the alpha mode.
     *
     * @param float  $alpha     the alpha channel from 0 (fully transparent) to 1 (fully opaque)
     * @param string $blendMode the blend mode. One of the following:
     *                          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
     *                          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
     *
     * @psalm-param non-empty-string $blendMode
     */
    public function setAlpha(float $alpha, string $blendMode = 'Normal'): static
    {
        $alpha = $this->validateRange($alpha, 0.0, 1.0);
        $index = $this->_addGsState(['stroking' => $alpha, 'blend_mode' => $blendMode]);
        $this->_setGsState($index);

        return $this;
    }

    protected function _enddoc(): void
    {
        if ([] !== $this->gsStates && $this->PDFVersion < '1.4') {
            $this->PDFVersion = '1.4';
        }
        parent::_enddoc();
    }

    protected function _putresourcedict(): void
    {
        parent::_putresourcedict();
        if ([] !== $this->gsStates) {
            $this->_put('/ExtGState <<');
            foreach ($this->gsStates as $key => $gsState) {
                $this->_putParams('/GS%d %d 0 R', $key, $gsState['index']);
            }
            $this->_put('>>');
        }
    }

    protected function _putresources(): void
    {
        if ([] !== $this->gsStates) {
            $this->_putGsStates();
        }
        parent::_putresources();
    }

    /**
     * @psalm-param ParameterType $parameters
     */
    private function _addGsState(array $parameters): int
    {
        $index = \count($this->gsStates) + 1;
        $this->gsStates[$index]['parameters'] = $parameters;

        return $index;
    }

    private function _putGsStates(): void
    {
        foreach (\array_keys($this->gsStates) as $key) {
            $this->_newobj();
            $this->gsStates[$key]['index'] = $this->n;
            $this->_put('<</Type /ExtGState');
            $parameters = $this->gsStates[$key]['parameters'];
            $this->_putParams('/ca %.3F', $parameters['stroking']);
            $this->_putParams('/CA %.3F', $parameters['stroking']);
            $this->_putParams('/BM /%s', $parameters['blend_mode']);
            $this->_put('>>');
            $this->_endobj();
        }
    }

    private function _setGsState(int $index): void
    {
        $this->_outParams('/GS%d gs', $index);
    }
}
