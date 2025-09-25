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

namespace App\Twig;

use App\Utils\StringUtils;
use Doctrine\SqlFormatter\Highlighter;
use Doctrine\SqlFormatter\HtmlHighlighter;
use Doctrine\SqlFormatter\SqlFormatter;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Attribute\AsTwigFilter;
use Twig\Environment;

/**
 * Twig extension to export and highlight variables.
 */
class HighlightExtension
{
    /**
     * @var array<non-empty-string, string>
     */
    private const PHP_PATTERNS = [
        '/title="(.*?)"/i' => '',
        '/data-depth=\d+\s/i' => '',
        '/data-indent-pad="\s+/i' => '',
        '/<script>.*<\/script>/i' => '',
        '/sf-dump/i' => 'highlight-php',
        '/<span class=highlight-php-note>array:\d+<\/span>/i' => '',
        '/ \[/i' => '[',
    ];

    private const SQL_REPLACES = [
        'array{' => '[',
        '}' => ']',
        '"backtick"' => '"highlight-sql-backtick"',
    ];

    private ?VarCloner $cloner = null;
    private ?HtmlDumper $dumper = null;
    private ?SqlFormatter $sqlFormatter = null;

    #[AsTwigFilter(name: 'var_export_php', needsEnvironment: true, isSafe: ['html'])]
    public function exportPhp(Environment $env, mixed $variable, string $id = ''): ?string
    {
        if (null === $variable || '' === $variable) {
            return null;
        }

        $cloner = $this->getCloner();
        $dumper = $this->getDumper($env);
        $data = $cloner->cloneVar($variable);
        /** @var resource $output */
        $output = \fopen('php://memory', 'r+');
        $dumper->dump($data, $output);
        $content = (string) \stream_get_contents($output, -1, 0);
        $content = StringUtils::pregReplaceAll(self::PHP_PATTERNS, $content);
        if ('' !== $id) {
            $content = StringUtils::pregReplace('/highlight-php-(\d+)/', $id, $content, 1);
        }

        return StringUtils::trim($content);
    }

    #[AsTwigFilter(name: 'var_export_sql', isSafe: ['html'])]
    public function exportSql(?string $sql): ?string
    {
        if (!StringUtils::isString($sql)) {
            return null;
        }

        $content = $this->getSqlFormatter()->format($sql);
        $content = StringUtils::replace(self::SQL_REPLACES, $content);

        return StringUtils::trim($content);
    }

    private function getCloner(): VarCloner
    {
        return $this->cloner ??= new VarCloner();
    }

    private function getDumper(Environment $env): HtmlDumper
    {
        if (!$this->dumper instanceof HtmlDumper) {
            $this->dumper = new HtmlDumper();
            $this->dumper->setDumpHeader('');
            $this->dumper->setCharset($env->getCharset());
        }

        return $this->dumper;
    }

    private function getSqlFormatter(): SqlFormatter
    {
        if (!$this->sqlFormatter instanceof SqlFormatter) {
            $highlighter = new HtmlHighlighter([
                HtmlHighlighter::HIGHLIGHT_PRE => 'class="highlight highlight-sql"',
                Highlighter::HIGHLIGHT_QUOTE => 'class="highlight-sql-quote"',
                Highlighter::HIGHLIGHT_BACKTICK_QUOTE => 'class="highlight-sql-backtick-quote"',
                Highlighter::HIGHLIGHT_RESERVED => 'class="highlight-sql-reserved"',
                Highlighter::HIGHLIGHT_BOUNDARY => 'class="highlight-sql-boundary"',
                Highlighter::HIGHLIGHT_NUMBER => 'class="highlight-sql-number"',
                Highlighter::HIGHLIGHT_WORD => 'class="highlight-sql-word"',
                Highlighter::HIGHLIGHT_ERROR => 'class="highlight-sql-error"',
                Highlighter::HIGHLIGHT_COMMENT => 'class="highlight-sql-comment"',
                Highlighter::HIGHLIGHT_VARIABLE => 'class="highlight-sql-variable"',
            ], true);

            $this->sqlFormatter = new SqlFormatter($highlighter);
        }

        return $this->sqlFormatter;
    }
}
