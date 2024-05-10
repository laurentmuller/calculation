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

use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use ReCaptcha\Response;

/**
 * Service to format a recaptcha response.
 */
class RecaptchaResponseService
{
    /**
     * Format the given response as HTML.
     */
    public function format(Response $response): string
    {
        $html = $this->formatLine('success', (string) \json_encode($response->isSuccess()), true);
        $html .= $this->formatLine('action', $response->getAction());
        $html .= $this->formatLine('score', FormatUtils::formatPercent($response->getScore()));
        $html .= $this->formatLine('hostname', $response->getHostname());
        $html .= $this->formatLine('challengeTs', $this->formatChallenge($response->getChallengeTs()));
        $html .= $this->formatLine('apkPackageName', $response->getApkPackageName());

        /** @psalm-var string[] $errorCodes $errorCodes */
        $errorCodes = $response->getErrorCodes();
        if ([] !== $errorCodes) {
            $html .= $this->formatLine('error-codes', \implode('<br>', $errorCodes));
        }

        return $html;
    }

    private function formatChallenge(string $value): string
    {
        $time = \strtotime($value);
        if (false === $time) {
            return $value;
        }

        return FormatUtils::formatDateTime($time, timeType: \IntlDateFormatter::MEDIUM);
    }

    private function formatLine(string $key, string $value, bool $separator = false): string
    {
        if (!StringUtils::isString($value)) {
            return '';
        }

        $html = '<div class="row">';
        $html .= '<div class="col-4 col-md-3 text-nowrap">' . $key . '</div>';
        $html .= '<div class="col-8 col-md-9 text-nowrap">&nbsp;:&nbsp;' . $value . '</div>';
        $html .= '</div>';
        if ($separator) {
            $html .= '<hr class="mt-2 mb-1">';
        }

        return $html;
    }
}
