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
    private const PATTERN = [
        '/title="(.*?)"/i' => '',
        '/data-depth=\d+\s/i' => '',
        '/<script>.*<\/script>/i' => '',
        '/sf-dump/i' => 'dump',
    ];

    private ?VarCloner $cloner = null;
    private ?HtmlDumper $dumper = null;

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('var_export_html', $this->export(...), [
                'needs_environment' => true,
                'is_safe' => ['html'],
            ]),
        ];
    }

    private function export(Environment $env, mixed $variable, int $maxDepth = 1): string
    {
        $cloner = $this->getCloner();
        $dumper = $this->getDumper($env, $maxDepth);
        $data = $cloner->cloneVar($variable);
        /** @psalm-var resource $output */
        $output = \fopen('php://memory', 'r+');
        $dumper->dump($data, $output);
        $content = (string) \stream_get_contents($output, -1, 0);
        $content = \preg_replace(\array_keys(self::PATTERN), \array_values(self::PATTERN), $content);

        return \trim($content);
    }

    private function getCloner(): VarCloner
    {
        if (!isset($this->cloner)) {
            $this->cloner = new VarCloner();
        }

        return $this->cloner;
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
