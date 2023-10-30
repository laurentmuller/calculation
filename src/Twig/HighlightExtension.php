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
use Doctrine\Bundle\DoctrineBundle\Twig\DoctrineExtension;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension to export and highlight variables.
 */
class HighlightExtension extends AbstractExtension
{
    /**
     * @const array<non-empty-string, string>
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
        '"keyword"' => '"highlight-sql-keyword"',
        '"word"' => '"highlight-sql-word"',
        '"variable"' => '"highlight-sql-variable"',
        '"symbol"' => '"highlight-sql-symbol"',
        '"comment"' => '"highlight-sql-comment"',
        '"backtick"' => '"highlight-sql-backtick"',
        '"string"' => '"highlight-sql-string"',
        '"number"' => '"highlight-sql-number"',
        '"error"' => '"highlight-sql-error"',
    ];

    private ?VarCloner $cloner = null;
    private ?DoctrineExtension $doctrine = null;
    private ?HtmlDumper $dumper = null;

    public function getFilters(): array
    {
        $options = [
            'needs_environment' => true,
            'is_safe' => ['html'],
        ];

        return [
            new TwigFilter('var_export_php', $this->exportPhp(...), $options),
            new TwigFilter('var_export_sql', $this->exportSql(...), $options),
        ];
    }

    private function exportPhp(Environment $env, mixed $variable, string $id = ''): ?string
    {
        if (null === $variable || '' === $variable) {
            return null;
        }
        $cloner = $this->getCloner();
        $dumper = $this->getDumper($env);
        $data = $cloner->cloneVar($variable);
        /** @psalm-var resource $output */
        $output = \fopen('php://memory', 'r+');
        $dumper->dump($data, $output);
        $content = \stream_get_contents($output, -1, 0);
        if (!\is_string($content)) {
            return null;
        }
        $content = StringUtils::pregReplace(self::PHP_PATTERNS, $content);
        if ('' !== $id) {
            $content = (string) \preg_replace('/highlight-php-\d+/', $id, $content);
        }

        return \trim($content);
    }

    private function exportSql(Environment $env, ?string $sql): ?string
    {
        if (null === $sql || '' === $sql) {
            return null;
        }
        $doctrine = $this->getDoctrine($env);
        $content = $doctrine->formatSql($sql, true);
        $content = StringUtils::replace(self::SQL_REPLACES, $content);

        return \trim($content);
    }

    private function getCloner(): VarCloner
    {
        return $this->cloner ??= new VarCloner();
    }

    private function getDoctrine(Environment $env): DoctrineExtension
    {
        return $this->doctrine ??= $env->getExtension(DoctrineExtension::class);
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
}
