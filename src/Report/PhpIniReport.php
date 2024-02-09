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
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\Enums\PdfFontStyle;
use App\Pdf\PdfColumn;
use App\Pdf\PdfException;
use App\Pdf\PdfGroupTable;
use App\Pdf\PdfStyle;
use App\Service\PhpInfoService;
use App\Utils\StringUtils;

/**
 * Report for php.ini.
 *
 * @psalm-type EntriesType = array{local: string, master: string}|string
 */
class PhpIniReport extends AbstractReport
{
    public function __construct(AbstractController $controller, private readonly PhpInfoService $service)
    {
        parent::__construct($controller);
        $file = \php_ini_loaded_file();
        if (\is_string($file)) {
            $description = $this->trans('log.list.file', ['%file%' => $file]);
            $this->getHeader()->setDescription($description);
        }
        $version = $this->service->getVersion();
        $this->setTitleTrans('about.php_version', ['%version%' => $version]);
    }

    /**
     * @throws PdfException
     */
    public function render(): bool
    {
        $this->AddPage();
        $content = $this->service->asArray();
        if ([] === $content) {
            $this->Cell(txt: $this->trans('about.error'));

            return true;
        }

        \ksort($content, \SORT_STRING | \SORT_FLAG_CASE);
        $table = PdfGroupTable::instance($this)
            ->setGroupStyle(PdfStyle::getHeaderStyle())
            ->addColumns(
                PdfColumn::left('Directive', 40),
                PdfColumn::left('Local Value', 30),
                PdfColumn::left('Master Value', 30)
            )->outputHeaders();

        /**  @psalm-var array<string, EntriesType> $entries */
        foreach ($content as $key => $entries) {
            $this->outputEntries($table, $key, $entries);
        }

        return true;
    }

    private function convert(mixed $var): string
    {
        return \htmlspecialchars_decode((string) $var);
    }

    private function getCellStyle(string $var): ?PdfStyle
    {
        $color = null;
        $style = PdfFontStyle::REGULAR;
        if (1 === \preg_match('/#[\dA-Fa-f]{6}/i', $var)) {
            $color = PdfTextColor::create($var);
        } elseif (\in_array(\strtolower($var), ['no', 'disabled', 'off', '(none)'], true)) {
            $color = PdfTextColor::darkGray();
        } elseif (StringUtils::equalIgnoreCase(PhpInfoService::REDACTED, $var)) {
            $color = PdfTextColor::darkGray();
        } elseif (StringUtils::equalIgnoreCase('no value', $var)) {
            $color = PdfTextColor::darkGray();
            $style = PdfFontStyle::ITALIC;
        }
        if (!$color instanceof PdfTextColor) {
            return null;
        }

        return PdfStyle::getCellStyle()
            ->setTextColor($color)
            ->setFontStyle($style);
    }

    /**
     * @psalm-param array<string, EntriesType> $entries
     *
     * @throws PdfException
     */
    private function outputEntries(PdfGroupTable $table, string $key, array $entries): void
    {
        if ([] === $entries) {
            return;
        }

        $this->addBookmark($key);
        $table->setGroupKey($key);
        $this->sortEntries($entries);

        foreach ($entries as $entryKey => $entryValue) {
            if (\is_array($entryValue)) {
                $local = $this->convert($entryValue['local']);
                $master = $this->convert($entryValue['master']);
                $table->startRow()
                    ->add($this->convert($entryKey))
                    ->add(text: $local, style: $this->getCellStyle($local))
                    ->add(text: $master, style: $this->getCellStyle($master))
                    ->endRow();
            } else {
                $value = $this->convert($entryValue);
                $table->startRow()
                    ->add($this->convert($entryKey))
                    ->add($value, 2, $this->getCellStyle($value))
                    ->endRow();
            }
        }
    }

    /**
     * @psalm-param array<string, EntriesType> $entries
     */
    private function sortEntries(array &$entries): void
    {
        \uksort($entries, function (string $a, string $b) use ($entries): int {
            $isArrayA = \is_array($entries[$a]);
            $isArrayB = \is_array($entries[$b]);
            if ($isArrayA !== $isArrayB) {
                return $isArrayA <=> $isArrayB;
            }

            return \strcasecmp($a, $b);
        });
    }
}
