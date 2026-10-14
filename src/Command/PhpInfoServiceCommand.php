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

namespace App\Command;

use App\Service\PhpInfoService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\Response;

#[AsCommand(name: 'app:phpinfo', description: 'Validate URL from PHP information.')]
class PhpInfoServiceCommand
{
    use WatchTrait;

    private const array CURL_OPTIONS = [
        \CURLOPT_NOBODY => true,
        \CURLOPT_RETURNTRANSFER => true,
        \CURLOPT_FOLLOWLOCATION => true,
        \CURLOPT_MAXREDIRS => 5,
        \CURLOPT_TIMEOUT => 10,
        \CURLOPT_CONNECTTIMEOUT => 5,
        \CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Validator/1.0', // Évite d'être bloqué par les pare-feux
    ];

    public function __construct(private readonly PhpInfoService $service)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $empty = [];
        $valid = [];
        $invalid = [];

        $this->start();
        $info = $this->service->getPhpInfo();
        $modules = $info['modules'];
        $io->title(\sprintf('URL validation for PHP v%s', $info['version']));
        foreach ($io->progressIterate($modules) as $module) {
            $url = $module['url'];
            if (null === $url) {
                $empty[] = $module['name'];
                continue;
            }

            if (false === \filter_var($url, \FILTER_VALIDATE_URL)) {
                $invalid[$module['name']] = $url;
                continue;
            }

            if (!$this->validateUrl($url)) {
                $invalid[$module['name']] = $url;
                continue;
            }

            $valid[$module['name']] = $url;
        }

        $message = \sprintf(
            'Modules: %d, Empty: %d, Valid: %d, Invalid: %d, %s.',
            \count($modules),
            \count($empty),
            \count($valid),
            \count($invalid),
            $this->stop()
        );

        if (0 !== \count($empty)) {
            $io->writeln('<comment>Module(s) with an empty URL:</>');
            foreach ($empty as $name) {
                $io->writeln(\sprintf('- %s', $name));
            }
            $io->newLine();
        }

        if (0 !== \count($invalid)) {
            $io->writeln('<comment>Module(s) with an invalid URL:</>');
            foreach ($invalid as $name => $url) {
                $io->writeln(\sprintf('- %s: %s', $name, $url));
            }
            $io->newLine();
            $io->warning($message);

            return Command::FAILURE;
        }

        $io->success($message);

        return Command::SUCCESS;
    }

    private function validateUrl(string $url): bool
    {
        $ch = \curl_init($url);
        if (false === $ch) {
            return false;
        }

        \curl_setopt_array($ch, self::CURL_OPTIONS);
        $response = \curl_exec($ch);
        if (false === $response) {
            \curl_close($ch);

            return false;
        }

        $httpCode = \curl_getinfo($ch, \CURLINFO_RESPONSE_CODE);
        \curl_close($ch);

        return Response::HTTP_OK === $httpCode;
    }
}
