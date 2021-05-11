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

namespace App\Tests;

use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait to log error in HTML file.
 *
 * @author Laurent Muller
 */
trait LogErrorTrait
{
    /**
     * @throws IOException
     */
    private function getLogDir(): string
    {
        $basedir = $this->client->getKernel()->getLogDir();
        $logDir = $basedir . '/tests';
        $fs = new Filesystem();
        if (!$fs->exists($logDir)) {
            $fs->mkdir($logDir);
        }

        return $logDir;
    }

    private function writeErrorFile(\Throwable $e): void
    {
        $testClass = static::class;
        $testName = $this->getName();

        $message = $e->getMessage();
        $trace = $e->getTraceAsString();
        $response = (string) $this->client->getResponse()->getContent();

        // Generate a file name containing the test file name and the test name, e.g. App_Tests_Controller_MyControllerTest___testDefault.html
        $fileName = \str_replace('\\', '_', "$testClass" . "_$testName.html");

        $pos = \stripos($response, '</body>');
        if (false !== $pos) {
            $left = \substr($response, 0, $pos);
            $middle = "<div class='container'><pre>Error message: $message\nFailing test: $testClass::$testName\nStacktrace:\n$trace</pre></div>";
            $right = \substr($response, $pos);
            $content = $left . $middle . $right;
        } else {
            $content = "<html>$response<pre>Error message: $message\nFailing test: $testClass::$testName\nStacktrace:\n$trace</pre></html>";
        }

        $logDir = $this->getLogDir();
        \file_put_contents("$logDir/$fileName", $content);
    }
}
