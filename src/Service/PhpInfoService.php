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

namespace App\Service;

use App\Utils\StringUtils;

/**
 * Utility class to get PHP information.
 *
 * @phpstan-type ValueType = float|int|string
 * @phpstan-type EntryType = array{local: ValueType, master: ValueType}|ValueType
 * @phpstan-type EntriesType = array<string, array<string, EntryType>>
 */
class PhpInfoService
{
    private const DISABLED = ['off', 'no', 'disabled', 'not enabled'];
    private const ENABLED = ['active', 'on', 'yes', 'enabled', 'supported'];
    private const REDACTED = '********';

    /**
     * Gets PHP information as the array.
     *
     * @phpstan-return EntriesType
     */
    public function asArray(): array
    {
        $content = $this->asText();
        $content = \strip_tags($content, '<h2><th><td>');
        $content = StringUtils::pregReplaceAll(
            [
                '/<th[^>]*>([^<]+)<\/th>/i' => '<info>\1</info>',
                '/<td[^>]*>([^<]+)<\/td>/i' => '<info>\1</info>',
            ],
            $content
        );

        $result = [];
        $matchs = null;
        $regexInfo = '<info>([^<]+)<\/info>';
        $regex2cols = \sprintf('/%1$s\s*%1$s/i', $regexInfo);
        $regex3cols = \sprintf('/%1$s\s*%1$s\s*%1$s/i', $regexInfo);
        $regexLine = '/<h2[^>]*>([^<]+)<\/h2>/i';

        /** @var array<int, string> $array */
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);
        foreach ($array as $index => $entry) {
            if (StringUtils::pregMatch($regexLine, $entry, $matchs)) {
                $name = \trim($matchs[1]);
                $vals = StringUtils::splitLines($array[$index + 1]);
                foreach ($vals as $val) {
                    if (StringUtils::pregMatch($regex3cols, $val, $matchs)) {
                        // 3 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $match3 = $this->convert(\trim($matchs[3]));
                        if (!StringUtils::equalIgnoreCase('directive', $match1)) {
                            $result[$name][$match1] = [
                                'local' => $match2,
                                'master' => $match3,
                            ];
                        }
                    } elseif (StringUtils::pregMatch($regex2cols, $val, $matchs)) {
                        // 2 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $result[$name][$match1] = $match2;
                    }
                }
            }
        }
        \ksort($result, \SORT_STRING | \SORT_FLAG_CASE);

        return $result;
    }

    /**
     * Gets PHP information as HTML.
     */
    public function asHtml(): string
    {
        $info = $this->asText();
        $info = StringUtils::pregReplace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
        $info = StringUtils::pregReplace('/<a\s(.+?)>(.+?)<\/a>/mi', '<p>$2</p>', $info);
        $info = \str_ireplace('background-color: white; text-align: center', '', $info);
        $info = \str_ireplace('<i>no value</i>', '<i class="text-secondary">No value</i>', $info);
        $info = \str_ireplace('(none)', '<i class="text-secondary">None</i>', $info);
        $info = \str_ireplace('<table>', "<table class='table table-sm table-hover mb-0'>", $info);
        $info = \str_ireplace(self::REDACTED, \sprintf('<i class="text-secondary">%s</i>', self::REDACTED), $info);

        foreach (['Directive', 'Local Value', 'Master Value'] as $value) {
            $info = \str_replace($value, \sprintf('<span class="fw-bold">%s</span>', $value), $info);
        }

        foreach (self::ENABLED as $value) {
            $search = \sprintf('/<td class="v">%s\s?<\/td>/mi', $value);
            $replace = \sprintf('<td class="v enabled">%s</td>', StringUtils::capitalize($value));
            $info = StringUtils::pregReplace($search, $replace, $info);

            $search = \sprintf('/<th>%s\s?<\/th>/mi', $value);
            $replace = \sprintf('<td class="v enabled">%s</td>', StringUtils::capitalize($value));
            $info = StringUtils::pregReplace($search, $replace, $info);
        }

        foreach (self::DISABLED as $value) {
            $search = \sprintf('/<td class="v">%s\s?<\/td>/mi', $value);
            $replace = \sprintf('<td class="v disabled">%s</td>', StringUtils::capitalize($value));
            $info = StringUtils::pregReplace($search, $replace, $info);

            $search = \sprintf('/<th>%s\s?<\/th>/mi', $value);
            $replace = \sprintf('<td class="v disabled">%s</td>', StringUtils::capitalize($value));
            $info = StringUtils::pregReplace($search, $replace, $info);
        }

        return StringUtils::pregReplace('/<table\s(.+?)>(.+?)<\/table>/is', '', $info, 1);
    }

    /**
     * Gets PHP information as text (raw data).
     */
    public function asText(): string
    {
        \ob_start();
        \phpinfo();
        $content = (string) \ob_get_contents();
        \ob_end_clean();

        return $this->updateContent($content);
    }

    /**
     * Gets the loaded extensions.
     *
     * @return string[]
     */
    public function getLoadedExtensions(): array
    {
        $extensions = \array_map(strtolower(...), \get_loaded_extensions());
        \sort($extensions);

        return $extensions;
    }

    /**
     * Gets the PHP version.
     */
    public function getVersion(): string
    {
        return \PHP_VERSION;
    }

    /**
     * Returns if the given value is a color.
     */
    public function isColor(string $value): bool
    {
        return StringUtils::pregMatch('/#[\da-f]{6}/i', $value);
    }

    /**
     * Returns if the given value is equal to the redacted value or equal to one of this disabled values,
     * ignoring case consideration.
     */
    public function isDisabled(string $value): bool
    {
        $value = \strtolower($value);

        return self::REDACTED === $value || \in_array($value, self::DISABLED, true);
    }

    /**
     * Returns if the given value is equal to 'no value', ignoring case consideration.
     */
    public function isNoValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase('no value', $value);
    }

    private function convert(string $var): string|int|float
    {
        if (\in_array(\strtolower($var), self::DISABLED, true)) {
            return StringUtils::capitalize($var);
        }
        if (\in_array(\strtolower($var), self::ENABLED, true)) {
            return StringUtils::capitalize($var);
        }
        if (StringUtils::pregMatch('/^-?\d+$/', $var)) {
            return (int) $var;
        }
        if (StringUtils::pregMatch('/^-?\d+\.\d+$/', $var)) {
            $pos = (int) \strrpos($var, '.');
            $decimals = \strlen($var) - $pos - 1;

            return \round((float) $var, $decimals);
        }

        return \str_replace('\\', '/', $var);
    }

    private function updateContent(string $content): string
    {
        $subst = \sprintf('$1%s$3', self::REDACTED);
        $keys = ['_KEY', '_USER_NAME', 'APP_SECRET', '_PASSWORD', 'MAILER_DSN', 'DATABASE_URL'];
        foreach ($keys as $key) {
            $regex = \sprintf("/(<tr.*\['.*%s']<\/td><td.*?>)(.*)(<.*<\/tr>)/mi", $key);
            $content = StringUtils::pregReplace($regex, $subst, $content);
        }
        $content = \str_replace('no value', 'No value', $content);
        $content = \str_replace(['✘ ', '✔ ', '⊕'], '', $content);
        $content = \str_ireplace(' </td>', '</td>', $content);

        return \trim($content);
    }
}
