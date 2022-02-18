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
     * <li>'yes', 'enabled' and 'on' values are converted to boolean true.</li>
     * <li>'no', 'disabled' and'off' values are converted to boolean false.</li>
     * <li>if applicable values are converted to integer or float.</li>
     * </ul>.
     */
    public function asArray(): array
    {
        $content = $this->asText(\INFO_MODULES);

        $content = \strip_tags($content, '<h2><th><td>');
        $content = (string) \preg_replace('/<th[^>]*>([^<]+)<\/th>/', '<info>\1</info>', $content);
        $content = (string) \preg_replace('/<td[^>]*>([^<]+)<\/td>/', '<info>\1</info>', $content);
        $array = (array) \preg_split('/(<h2[^>]*>[^<]+<\/h2>)/', $content, -1, \PREG_SPLIT_DELIM_CAPTURE);

        $result = [];
        $count = \count($array);

        $regexInfo = '<info>([^<]+)<\/info>';
        $regex3cols = '/' . $regexInfo . '\s*' . $regexInfo . '\s*' . $regexInfo . '/';
        $regex2cols = '/' . $regexInfo . '\s*' . $regexInfo . '/';

        $matchs = null;
        $directive1 = null;
        $directive2 = null;
        for ($i = 1; $i < $count; ++$i) {
            if (\preg_match('/<h2[^>]*>([^<]+)<\/h2>/', (string) $array[$i], $matchs)) {
                $name = \trim($matchs[1]);
                $vals = \explode("\n", (string) $array[$i + 1]);
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
     */
    public function asHtml(): string
    {
        // get info
        $info = $this->asText();

        // extract body
        $info = (string) \preg_replace('%^.*<body>(.*)</body>.*$%ms', '$1', $info);

        // remove links
        $info = (string) \preg_replace('/<a\s(.+?)>(.+?)<\/a>/is', '<p>$2</p>', $info);

        // remove sensitive informations
        $info = (string) \preg_replace('/<tr>.*KEY.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*MAILER_DSN.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*DATABASE_URL.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*DATABASE_EDIT.*<\/tr>/m', '', $info);
        $info = (string) \preg_replace('/<tr>.*PASSWORD.*<\/tr>/m', '', $info);

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

        return $content;
    }

    /**
     * Converts the given variable.
     *
     * @param mixed $var the variable to convert
     *
     * @return string|int|float|bool the converted variable
     */
    private function convert($var)
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
}
