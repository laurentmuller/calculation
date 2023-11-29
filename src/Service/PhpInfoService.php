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
final class PhpInfoService
{
    /**
     * Gets PHP information as array.
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     *
     * @psalm-return array<string, array<string, array{local: scalar, master: scalar}|scalar>>
     */
    public function asArray(int $what = \INFO_ALL): array
    {
        $content = $this->asText($what);
        $content = \strip_tags($content, '<h2><th><td>');
        $content = StringUtils::pregReplace(
            [
                '/<th[^>]*>([^<]+)<\/th>/i' => '<info>\1</info>',
                '/<td[^>]*>([^<]+)<\/td>/i' => '<info>\1</info>',
            ],
            $content
        );

        $result = [];
        $matchs = null;
        $regexInfo = '<info>([^<]+)<\/info>';
        $regex3cols = '/' . $regexInfo . '\s*' . $regexInfo . '\s*' . $regexInfo . '/i';
        $regex2cols = '/' . $regexInfo . '\s*' . $regexInfo . '/i';
        $regexLine = '/<h2[^>]*>([^<]+)<\/h2>/i';
        /** @psalm-var array<int, string> $array */
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);
        foreach ($array as $index => $entry) {
            if (\preg_match($regexLine, $entry, $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode(StringUtils::NEW_LINE, $array[$index + 1]);
                foreach ($vals as $val) {
                    if (\preg_match($regex3cols, $val, $matchs)) {
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
                    } elseif (\preg_match($regex2cols, $val, $matchs)) {
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
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     */
    public function asHtml(int $what = \INFO_ALL): string
    {
        $info = $this->asText($what);

        $info = (string) \preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);
        $info = (string) \preg_replace('/<a\s(.+?)>(.+?)<\/a>/mi', '<p>$2</p>', $info);
        $info = \str_ireplace('background-color: white; text-align: center', '', $info);
        $info = \str_ireplace('<i>no value</i>', '<i class="text-secondary">No value</i>', $info);
        $info = \str_ireplace('(none)', '<i class="text-secondary">None</i>', $info);
        $info = \str_ireplace('<table>', "<table class='table table-hover table-sm mb-0'>", $info);

        foreach (['Directive', 'Local Value', 'Master Value'] as $value) {
            $info = \str_replace($value, '<span class="fw-bold">' . $value . '</span>', $info);
        }

        foreach (['on', 'yes', 'enabled'] as $value) {
            $search = '/<td class="v">' . $value . '\s?<\/td>/mi';
            $replace = '<td class="v enabled">' . StringUtils::capitalize($value) . '</td>';
            $info = (string) \preg_replace($search, $replace, $info);

            $search = '/<th>' . $value . '\s?<\/th>/mi';
            $replace = '<td class="v enabled">' . StringUtils::capitalize($value) . '</td>';
            $info = (string) \preg_replace($search, $replace, $info);
        }

        foreach (['off', 'no', 'disabled'] as $value) {
            $search = '/<td class="v">' . $value . '\s?<\/td>/mi';
            $replace = '<td class="v disabled">' . StringUtils::capitalize($value) . '</td>';
            $info = (string) \preg_replace($search, $replace, $info);

            $search = '/<th>' . $value . '\s?<\/th>/mi';
            $replace = '<td class="v disabled">' . StringUtils::capitalize($value) . '</td>';
            $info = (string) \preg_replace($search, $replace, $info);
        }

        return (string) \preg_replace('/<table\s(.+?)>(.+?)<\/table>/is', '', $info, 1);
    }

    /**
     * Gets PHP information as text (raw data).
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
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
        if (\in_array(\strtolower($var), ['yes', 'no', 'enabled', 'disabled', 'on', 'off', 'no value'], true)) {
            return StringUtils::capitalize($var);
        }
        if (\preg_match('/^-?\d+$/', $var)) {
            return (int) $var;
        }
        if (\preg_match('/^-?\d+\.\d+$/', $var)) {
            $pos = (int) \strrpos($var, '.');
            $decimals = \strlen($var) - $pos - 1;

            return \round((float) $var, $decimals);
        }

        return \str_replace('\\', '/', $var);
    }

    private function updateContent(string $content): string
    {
        $subst = '$1******$3';
        $keys = ['_KEY', '_USER_NAME', 'APP_SECRET', '_PASSWORD', 'MAILER_DSN', 'DATABASE_URL'];
        foreach ($keys as $key) {
            $regex = "/(<tr.*\['.*$key']<\/td><td.*?>)(.*)(<.*<\/tr>)/mi";
            $content = (string) \preg_replace($regex, $subst, $content);
        }
        $content = \str_replace(['✘ ', '✔ ', '⊕'], '', $content);

        return \trim($content);
    }
}
