<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

/**
 * This trait allows class to have an alignment property. The default value is <code>PdfConstantsInterface::ALIGN_LEFT</code>.
 *
 * @author Laurent Muller
 */
trait PdfAlignmentTrait
{
    /**
     * The alignment.
     */
    protected string $alignment = PdfConstantsInterface::ALIGN_LEFT;

    /**
     * Gets the alignment.
     *
     * @return string|null one of the ALIGN_XX constants.The value can be:
     *                     <ul>
     *                     <li>'' (an empty string): inherit.</li>
     *                     <li>'<b>L</b>' : left align.</li>
     *                     <li>'<b>C</b>' : center.</li>
     *                     <li>'<b>R</b>' : right align.</li>
     *                     <li>'<b>J</b>' : justification.</li>
     *                     </ul>
     */
    public function getAlignment(): ?string
    {
        return $this->alignment;
    }

    /**
     * Returns if this alignment is inherited.
     *
     * @return bool true if inherited
     */
    public function isAlignmentInherited(): bool
    {
        return empty($this->alignment);
    }

    /**
     * Sets the alignment.
     *
     * @param string|null $alignment one of the ALIGN_XX constants. The value can be:
     *                               <ul>
     *                               <li>'' (an empty string): inherit.</li>
     *                               <li>'<b>L</b>' : left align.</li>
     *                               <li>'<b>C</b>' : center.</li>
     *                               <li>'<b>R</b>' : right align.</li>
     *                               <li>'<b>J</b>' : justification.</li>
     *                               </ul>
     */
    public function setAlignment(?string $alignment): self
    {
        $this->alignment = $this->validateAlignment($alignment);

        return $this;
    }

    /**
     * Gets the textual representation of this alignment.
     *
     * @return string the textual representation
     */
    protected function getAlignmentText(): string
    {
        $result = 'Left';
        switch ($this->alignment) {
            case PdfConstantsInterface::ALIGN_RIGHT:
                $result = 'Right';
                break;

            case PdfConstantsInterface::ALIGN_CENTER:
                $result = 'Center';
                break;

            case PdfConstantsInterface::ALIGN_INHERITED:
                $result = 'Inherited';
                break;

            case PdfConstantsInterface::ALIGN_JUSTIFIED:
                $result = 'Justified';
                break;
        }

        return 'PdfAlignment(' . $result . ')';
    }

    /**
     * Validate the given alignment.
     *
     * @param string|null $alignment the alignment to validate
     *
     * @return string a valid alignment
     */
    protected function validateAlignment(?string $alignment): string
    {
        switch ($alignment) {
            case PdfConstantsInterface::ALIGN_LEFT:
            case PdfConstantsInterface::ALIGN_RIGHT:
            case PdfConstantsInterface::ALIGN_CENTER:
            case PdfConstantsInterface::ALIGN_INHERITED:
            case PdfConstantsInterface::ALIGN_JUSTIFIED:
                return $alignment;
            default:
                return PdfConstantsInterface::ALIGN_LEFT;
        }
    }
}
