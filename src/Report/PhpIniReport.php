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
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTextColor;
use App\Service\PhpInfoService;
use App\Utils\StringUtils;

/**
 * Report for php.ini.
 */
class PhpIniReport extends AbstractReport
{
    /**
     * Constructor.
     */
    public function __construct(AbstractController $controller, private readonly PhpInfoService $service)
    {
        parent::__construct($controller);
        if ($description = \php_ini_loaded_file()) {
            $this->getHeader()->setDescription($description);
        }
        $version = $this->service->getVersion();
        $this->setTitleTrans('about.php_version', ['%version%' => $version]);
    }

    public function render(): bool
    {
        $this->AddPage();
        $content = $this->service->asArray();
        if ([] === $content) {
            $this->Cell(txt: $this->trans('about.error'));

            return true;
        }
        \ksort($content, \SORT_STRING | \SORT_FLAG_CASE);
        $table = PdfGroupTableBuilder::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Directive', 40),
                PdfColumn::left('Local Value', 30),
                PdfColumn::left('Master Value', 30)
            )->outputHeaders();
        foreach ($content as $key => $entries) {
            $this->outputEntries($table, $key, $entries);
        }

        return true;
    }

    /**
     * @param mixed $var the variable to convert
     */
    private function convert(mixed $var): string
    {
        if (\is_bool($var)) {
            return \ucfirst(StringUtils::encodeJson($var));
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
        $style = PdfFontStyle::REGULAR;
        if (\preg_match('/#[\dA-Fa-f]{6}/i', $var)) {
            $color = PdfTextColor::create($var);
        } elseif (StringUtils::equalIgnoreCase('no value', $var)) {
            $color = PdfTextColor::darkGray();
            $style = PdfFontStyle::ITALIC;
        }
        if ($color instanceof PdfTextColor) {
            return PdfStyle::getCellStyle()->setTextColor($color)->setFontStyle($style);
        }

        return null;
    }

    /**
     * @psalm-param array<string, array{local: scalar, master: scalar}|scalar> $entries
     */
    private function outputEntries(PdfGroupTableBuilder $table, string $key, array $entries): void
    {
        $this->addBookmark($key);
        $table->setGroupKey($key);
        $this->sortEntries($entries);

        foreach ($entries as $key => $entry) {
            if (\is_array($entry)) {
                $local = $this->convert($entry['local']);
                $master = $this->convert($entry['master']);
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
     * @psalm-param array<string, array{local: scalar, master: scalar}|scalar> $entries
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
