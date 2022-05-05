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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfFont;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;

/**
 * Report for php.ini.
 */
class PhpIniReport extends AbstractReport
{
    /**
     * Constructor.
     *
     * @param array<string, array<string, mixed>> $content
     */
    public function __construct(AbstractController $controller, private readonly array $content, string $version)
    {
        parent::__construct($controller);
        if ($description = \php_ini_loaded_file()) {
            $this->header->setDescription($description);
        }
        $title = $this->trans('about.php');
        if (!empty($version)) {
            $title .= ' ' . $version;
        }
        $this->SetTitle($title);
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        $content = $this->content;
        if (empty($content)) {
            return false;
        }

        $this->AddPage();

        \ksort($content, \SORT_STRING | \SORT_FLAG_CASE);

        $table = new PdfGroupTableBuilder($this);
        $table->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumn(PdfColumn::left('Directive', 40))
            ->addColumn(PdfColumn::left('Local Value', 30))
            ->addColumn(PdfColumn::left('Master Value', 30))
            ->outputHeaders();

        foreach ($content as $key => $value) {
            $table->setGroupKey($key);
            $this->outputEntries($table, $value);
        }

        return true;
    }

    /**
     * @param mixed $var the variable to convert
     */
    private function convert(mixed $var): string
    {
        if (\is_bool($var)) {
            return \ucfirst((string) \json_encode($var));
        } else {
            return \htmlspecialchars_decode((string) $var);
        }
    }

    /**
     * Gets the cell style for the given value.
     */
    private function getCellStyle(string $var): ?PdfStyle
    {
        $color = null;
        $fontStyle = PdfFont::STYLE_REGULAR;
        if (\preg_match('/#[\dA-Fa-f]{6}/i', $var)) {
            $color = PdfTextColor::create($var);
        } elseif ('No value' === $var) {
            $color = PdfTextColor::create('#7F7F7F');
            $fontStyle = PdfFont::STYLE_ITALIC;
        }
        if (null !== $color) {
            return PdfStyle::getCellStyle()->setTextColor($color)->setFontStyle($fontStyle);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $entries
     */
    private function outputEntries(PdfGroupTableBuilder $table, array $entries): void
    {
        $this->sortEntries($entries);
        /** @var mixed $entry */
        foreach ($entries as $key => $entry) {
            if (\is_array($entry)) {
                $local = $this->convert(\reset($entry));
                $master = $this->convert(\end($entry));
                $table->startRow()
                    ->add($this->convert($key))
                    ->add(text: $local, style: $this->getCellStyle($local))
                    ->add(text: $master, style: $this->getCellStyle($master))
                    ->endRow();
            } else {
                $value = $this->convert($entry);
                $table->startRow()
                    ->add($this->convert($key))
                    ->add($value, 2, $this->getCellStyle($value))
                    ->endRow();
            }
        }
    }

    /**
     * @param array<string, mixed> $entries
     */
    private function sortEntries(array &$entries): void
    {
        \uksort($entries, function (string $a, string $b) use ($entries): int {
            $isArrayA = \is_array($entries[$a]);
            $isArrayB = \is_array($entries[$b]);
            if ($isArrayA !== $isArrayB) {
                return $isArrayA <=> $isArrayB;
            } else {
                return \strcasecmp($a, $b);
            }
        });
    }
}
