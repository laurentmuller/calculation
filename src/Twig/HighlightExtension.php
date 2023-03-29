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

use App\Util\StringUtils;
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
    private const PHP_PATTERNS = [
        '/title="(.*?)"/i' => '',
        '/data-depth=\d+\s/i' => '',
        '/data-indent-pad="\s+/i' => '',
        '/<script>.*<\/script>/i' => '',
        '/sf-dump/i' => 'highlight-php',
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

    /**
     * {@inheritdoc}
     */
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

    private function exportPhp(Environment $env, mixed $variable, string $id = '', int $maxDepth = 1): ?string
    {
        if (null === $variable || '' === $variable) {
            return null;
        }
        $cloner = $this->getCloner();
        $dumper = $this->getDumper($env, $maxDepth);
        $data = $cloner->cloneVar($variable);
        /** @psalm-var resource $output */
        $output = \fopen('php://memory', 'r+');
        $dumper->dump($data, $output);
        $content = (string) \stream_get_contents($output, -1, 0);
        $content = StringUtils::pregReplace(self::PHP_PATTERNS, $content);
        if ('' !== $id) {
            $content = \preg_replace('/highlight-php-\d+/', $id, $content);
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
        if (!isset($this->cloner)) {
            $this->cloner = new VarCloner();
        }

        return $this->cloner;
    }

    private function getDoctrine(Environment $env): DoctrineExtension
    {
        if (!isset($this->doctrine)) {
            $this->doctrine = $env->getExtension(DoctrineExtension::class);
        }

        return $this->doctrine;
    }

    private function getDumper(Environment $env, int $maxDepth): HtmlDumper
    {
        if (!isset($this->dumper)) {
            $this->dumper = new HtmlDumper();
            $this->dumper->setDumpHeader('');
            $this->dumper->setCharset($env->getCharset());
        }
        $this->dumper->setDisplayOptions([
            'maxDepth' => $maxDepth,
        ]);

        return $this->dumper;
    }
}
