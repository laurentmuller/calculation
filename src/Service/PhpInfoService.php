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
 */
class PhpInfoService
{
    /**
     * The replaced string for sensitive parameters.
     */
    public const REDACTED = '********';

    private const CONVERT = ['yes', 'no', 'enabled', 'disabled', 'on', 'off', 'no value'];
    private const DISABLED = ['off', 'no', 'disabled'];
    private const ENABLED = ['on', 'yes', 'enabled'];

    /**
     * Gets PHP information as the array.
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values
     *                  summed together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  with the bitwise or operator.
     *
     * @psalm-return array<string, array<string, array{local: scalar, master: scalar}|scalar>>
     */
    public function asArray(int $what = \INFO_ALL): array
    {
        $content = $this->asText($what);
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

        /** @psalm-var array<int, string> $array */
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);
        foreach ($array as $index => $entry) {
            if (StringUtils::pregMatch($regexLine, $entry, $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode(StringUtils::NEW_LINE, $array[$index + 1]);
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

        return $result;
    }

    /**
     * Gets PHP information as HTML.
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise
     *                  values summed together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  with the bitwise or operator.
     */
    public function asHtml(int $what = \INFO_ALL): string
    {
        $info = $this->asText($what);

        $info = StringUtils::pregReplace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
        $info = StringUtils::pregReplace('/<a\s(.+?)>(.+?)<\/a>/mi', '<p>$2</p>', $info);
        $info = \str_ireplace('background-color: white; text-align: center', '', $info);
        $info = \str_ireplace('<i>no value</i>', '<i class="text-secondary">No value</i>', $info);
        $info = \str_ireplace('(none)', '<i class="text-secondary">None</i>', $info);
        $info = \str_ireplace('<table>', "<table class='table table-sm mb-0'>", $info);
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
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values
     *                  summed together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  with the bitwise or operator.
     */
    public function asText(int $what = \INFO_ALL): string
    {
        \ob_start();
        \phpinfo($what);
        $content = (string) \ob_get_contents();
        \ob_end_clean();

        return $this->updateContent($content);
    }

    /**
     * Gets the PHP version.
     */
    public function getVersion(): string
    {
        return \PHP_VERSION;
    }

    private function convert(string $var): string|int|float
    {
        if (\in_array(\strtolower($var), self::CONVERT, true)) {
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
        $content = \str_replace(['✘ ', '✔ ', '⊕'], '', $content);

        return \trim($content);
    }
}
