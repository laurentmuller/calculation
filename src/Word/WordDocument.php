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

namespace App\Word;

use App\Utils\FormatUtils;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Zoom;
use PhpOffice\PhpWord\Style\Language;

/**
 * Extend the PHPWord.
 */
class WordDocument extends PhpWord
{
    private ?string $title = null;

    public function __construct()
    {
        parent::__construct();
        $this->initialize();
    }

    /**
     * Get document title.
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set document title.
     */
    public function setTitle(?string $title): static
    {
        $this->title = $title;
        $this->getDocInfo()->setTitle($title ?? '');

        return $this;
    }

    /**
     * Initialize settings.
     */
    protected function initialize(): void
    {
        $settings = $this->getSettings();
        $settings->setZoom(Zoom::BEST_FIT);
        $settings->setDecimalSymbol(FormatUtils::getDecimal());
        $settings->setThemeFontLang(new Language(\Locale::getDefault()));
        Settings::setOutputEscapingEnabled(true);
    }
}
