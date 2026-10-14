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
use App\Utils\StringUtils;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(name: 'app:phpinfo', description: 'Validate URL from PHP information.')]
class PhpInfoServiceCommand
{
    use WatchTrait;

    public function __construct(private readonly PhpInfoService $service)
    {
    }

    public function __invoke(SymfonyStyle $io): int
    {
        $empty = [];
        $valid = [];
        $invalid = [];

        $this->start();
        $client = HttpClient::create();
        $info = $this->service->getPhpInfo();
        $modules = $info['modules'];
        $io->title(\sprintf('URL validation for PHP v%s', $info['version']));
        foreach ($io->progressIterate($modules) as $module) {
            $url = $module['url'];
            $name = $module['name'];
            if (!StringUtils::isString($url)) {
                $empty[] = $name;
                continue;
            }

            if (!$this->filterUrl($url)) {
                $invalid[$name] = $url;
                continue;
            }

            if (!$this->validateUrl($client, $url)) {
                $invalid[$name] = $url;
                continue;
            }

            $valid[$name] = $url;
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

    private function filterUrl(string $url): bool
    {
        return false !== \filter_var($url, \FILTER_VALIDATE_URL);
    }

    private function validateUrl(HttpClientInterface $client, string $url): bool
    {
        try {
            $response = $client->request(Request::METHOD_HEAD, $url);
            $statusCode = $response->getStatusCode();

            return $statusCode >= Response::HTTP_OK && $statusCode < Response::HTTP_BAD_REQUEST;
        } catch (TransportExceptionInterface) {
            return false;
        }
    }
}
