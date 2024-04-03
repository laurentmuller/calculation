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
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get or run commands.
 *
 * @psalm-type ArgumentType = array{
 *     name: string,
 *     is_required: bool,
 *     is_array: bool,
 *     description: string,
 *     default: array|string|bool|null}
 * @psalm-type OptionType = array{
 *     name: string,
 *     shortcut: string,
 *     accept_value: bool,
 *     is_value_required: bool,
 *     is_multiple: bool,
 *     description: string,
 *     default: array|string|bool|null}
 * @psalm-type CommandType = array{
 *     name: string,
 *     description: string,
 *     usage: string[],
 *     help: string,
 *     hidden: bool,
 *     definition: array {
 *         arguments: array<string, ArgumentType>,
 *         options: array<string, OptionType>}}
 * @psalm-type ContentType = array{
 *     commands:  CommandType[]}
 */
class CommandService
{
    // the key name to cache content.
    private const CACHE_KEY = 'cache.service.command';

    // the help replace
    private const HELP_REPLACE = [
        '<info>' => '<span class="text-success">',
        '<comment>' => '<span class="text-warning">',
        '</info>' => '</span>',
        '</comment>' => '</span>',
        "\n" => '<br>',
    ];

    public function __construct(
        private readonly KernelInterface $kernel,
        #[Target('cache.service.command')]
        private readonly CacheInterface $cache
    ) {
    }

    /**
     * Execute the given command.
     *
     * @param string $command    the command name to execute
     * @param array  $parameters the command parameters
     *
     * @return string the command output result
     *
     * @throws \Exception
     */
    public function execute(string $command, array $parameters = []): string
    {
        $parameters['command'] = $command;
        $input = new ArrayInput($parameters);
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $application->run($input, $output);

        return $output->fetch();
    }

    /**
     * @psalm-return CommandType|null
     *
     * @throws InvalidArgumentException
     *
     * @phpstan-ignore-next-line
     */
    public function getCommand(string $name): ?array
    {
        return $this->getCommands()[$name] ?? null;
    }

    /**
     * Gets all commands.
     *
     * @psalm-return array<string, CommandType>
     *
     * @throws InvalidArgumentException
     */
    public function getCommands(): array
    {
        return $this->cache->get(self::CACHE_KEY, function () {
            $content = $this->execute('list', ['--format' => 'json']);
            /** @phpstan-var ContentType $decoded */
            $decoded = StringUtils::decodeJson($content);

            $result = [];
            $commands = $decoded['commands'];
            $commands = \array_filter($commands, fn (array $command): bool => !$command['hidden']);
            foreach ($commands as $command) {
                $command['help'] = StringUtils::replace(self::HELP_REPLACE, $command['help']);
                foreach ($command['definition']['arguments'] as &$argument) {
                    $argument['description'] = StringUtils::replace(self::HELP_REPLACE, $argument['description']);
                }
                foreach ($command['definition']['options'] as &$option) {
                    $option['description'] = StringUtils::replace(self::HELP_REPLACE, $option['description']);
                }
                $result[$command['name']] = $command;
            }
            \ksort($result);

            return $result;
        });
    }
}
