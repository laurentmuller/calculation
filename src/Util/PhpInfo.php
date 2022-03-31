<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Util;

/**
 * Utility class to get PHP informations.
 *
 * @author Laurent Muller
 */
final class PhpInfo
{
    /**
     * Gets PHP informations as array.
     * Note:
     * <ul>
     * <li>'yes', 'enabled', 1 and 'on' values are converted to boolean true.</li>
     * <li>'no', 'disabled', 0 and'off' values are converted to boolean false.</li>
     * <li>if applicable values are converted to integer or float.</li>
     * </ul>
     * In all other case, values are returned as string.
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     */
    public function asArray(int $what = \INFO_ALL): array
    {
        $content = $this->asText($what);
        $content = \strip_tags($content, '<h2><th><td>');
        $content = (string) \preg_replace('/<th[^>]*>([^<]+)<\/th>/i', '<info>\1</info>', $content);
        $content = (string) \preg_replace('/<td[^>]*>([^<]+)<\/td>/i', '<info>\1</info>', $content);
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/i', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);

        $regexInfo = '<info>([^<]+)<\/info>';
        $regex3cols = '/' . $regexInfo . '\s*' . $regexInfo . '\s*' . $regexInfo . '/i';
        $regex2cols = '/' . $regexInfo . '\s*' . $regexInfo . '/i';
        $regexLine = '/<h2[^>]*>([^<]+)<\/h2>/i';

        $result = [];
        $matchs = null;
        $directive1 = null;
        $directive2 = null;
        foreach ($array as $index => $entry) {
            if (\preg_match($regexLine, (string) $entry, $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode("\n", (string) $array[$index + 1]);
                foreach ($vals as $val) {
                    if (\preg_match($regex3cols, $val, $matchs)) { // 3 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $match3 = $this->convert(\trim($matchs[3]));

                        // special case for 'Directive'
                        if (0 === \strcasecmp('directive', $match1)) {
                            $directive1 = $match2;
                            $directive2 = $match3;
                        } elseif ($directive1 && $directive2) {
                            $result[$name][$match1] = [
                                (string) $directive1 => $match2,
                                (string) $directive2 => $match3,
                            ];
                        } else {
                            $result[$name][$match1] = [$match2,  $match3];
                        }
                    } elseif (\preg_match($regex2cols, $val, $matchs)) { // 2 columns
                        $match1 = \trim($matchs[1]);
                        $match2 = $this->convert(\trim($matchs[2]));
                        $result[$name][$match1] = $match2;
                        $directive1 = $directive2 = null;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Gets PHP informations as HTML.
     *
     * @param int $what The output may be customized by passing one or more of the following constants bitwise values summed
     *                  together in the optional what parameter.
     *                  One can also combine the respective constants or bitwise values
     *                  together with the bitwise or operator.
     */
    public function asHtml(int $what = \INFO_ALL): string
    {
        // get info
        $info = $this->asText($what);

        // extract body
        $info = (string) \preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);

        // remove links
        $info = (string) \preg_replace('/<a\s(.+?)>(.+?)<\/a>/is', '<p>$2</p>', $info);

        // replace version
        $info = \str_replace('PHP Version', 'Version', $info);

        // update table class
        $info = \str_replace('<table>', "<table class='table table-hover table-sm mb-0'>", $info);

        return $info;
    }

    /**
     * Gets PHP informations as text (raw data).
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

        return $this->removeKeys($content, [
            '_KEY',
            'MAILER_DSN',
            'DATABASE_URL',
            'DATABASE_EDIT',
            'PASSWORD',
        ]);
    }

    /**
     * Gets the PHP version.
     */
    public function getVersion(): string
    {
        return \PHP_VERSION;
    }

    /**
     * Converts the given variable.
     */
    private function convert(mixed $var): string|int|float|bool
    {
        $value = \strtolower((string) $var);
        if (\in_array($value, ['yes', 'enabled', 'on', '1'], true)) {
            return true;
        } elseif (\in_array($value, ['no', 'disabled', 'off', '0'], true)) {
            return false;
        } elseif (\is_int($var) || \preg_match('/^-?\d+$/', $value)) {
            return (int) $value;
        } elseif (\is_float($var)) {
            return $var;
        } elseif (\preg_match('/^-?\d+\.\d+$/', $value)) {
            $pos = (int) \strrpos($value, '.');
            $decimals = \strlen($value) - $pos - 1;

            return \round((float) $value, $decimals);
        } elseif ('no value' === $value) {
            return 'No value';
        } else {
            return \str_replace('\\', '/', (string) $var);
        }
    }

    /**
     * @param string[] $keys
     */
    private function removeKeys(string $content, array $keys): string
    {
        foreach ($keys as $key) {
            $content = (string) \preg_replace("/<tr>.*$key.*<\/tr>/m", '', $content);
        }

        return $content;
    }
}
