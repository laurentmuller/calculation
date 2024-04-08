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

use App\Model\CommandResult;
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
 *     default: mixed,
 *     display: string}
 * @psalm-type OptionType = array{
 *     name: string,
 *     shortcut: string,
 *     accept_value: bool,
 *     is_value_required: bool,
 *     is_multiple: bool,
 *     description: string,
 *     default: mixed,
 *     display: string}
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
    private const HELP_REPLACE = [
        // command name
        '/development/public/index.php' => 'bin/console',
        '/development/public' => 'bin/console',
        '/index.php/command' => 'bin/console',
        'bin/console/content' => 'bin/console',
        'bin/console/pdf' => 'bin/console',
        // classes
        '<info>' => '<span class="text-success">',
        '<comment>' => '<span class="text-warning">',
        '</info>' => '</span>',
        '</comment>' => '</span>',
        '</>' => '</span>',
        // line break
        "\n" => '<br>',
    ];

    private const USAGE_REPLACE = [
        '/(\[.*)( \[--\])/m' => '[options]$2',
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
     * @return CommandResult the result of the execution
     *
     * @throws \Exception if the running command fail
     */
    public function execute(string $command, array $parameters = []): CommandResult
    {
        $parameters = ['command' => $command] + $parameters;
        $input = new ArrayInput($parameters);
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $status = $application->run($input, $output);
        $content = $output->fetch();
        unset($application);

        return new CommandResult($status, $content);
    }

    /**
     * Gets the command for the given name.
     *
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
        return $this->cache->get('cache.command.service', function () {
            $result = $this->execute('list', ['--format' => 'json']);
            if (!$result->isSuccess()) {
                return [];
            }

            /** @phpstan-var ContentType $decoded */
            $decoded = StringUtils::decodeJson($result->content);
            $result = \array_reduce(
                $decoded['commands'],
                /**
                 * @psalm-param array<string, CommandType> $carry
                 * @psalm-param CommandType $command
                 */
                function (array $carry, array $command): array {
                    if ($command['hidden']) {
                        return $carry;
                    }

                    $this->updateCommand($command);

                    return [$command['name'] => $command] + $carry;
                },
                []
            );
            \ksort($result);

            return $result;
        });
    }

    /**
     * Returns all command names.
     *
     * @return string[]
     *
     * @throws InvalidArgumentException
     */
    public function getNames(): array
    {
        return \array_keys($this->getCommands());
    }

    /**
     * Returns if the given command name exist.
     *
     * @throws InvalidArgumentException
     */
    public function hasCommand(string $name): bool
    {
        return \array_key_exists($name, $this->getCommands());
    }

    private function encodeDefaultValue(mixed $default): string
    {
        if (false === $default || null === $default) {
            return '';
        }
        if (\INF === $default) {
            return 'INF';
        }
        if ([] === $default) {
            return '[]';
        }

        return \str_replace('\\\\', '\\', (string) \json_encode($default, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE));
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-ignore-next-line
     */
    private function updateCommand(array &$command): void
    {
        if ($command['help'] === $command['description']) {
            $command['help'] = '';
        } else {
            $command['help'] = StringUtils::replace(self::HELP_REPLACE, $command['help']);
        }
        if (0 === \count($command['definition']['arguments'])) {
            $command['usage'] = [\sprintf('%s [options]', $command['name'])];
        } else {
            $command['usage'] = StringUtils::pregReplace(self::USAGE_REPLACE, $command['usage']);
        }
        foreach ($command['definition']['arguments'] as &$argument) {
            $argument['description'] = StringUtils::replace(self::HELP_REPLACE, $argument['description']);
            $argument['display'] = $this->encodeDefaultValue($argument['default']);
        }
        foreach ($command['definition']['options'] as &$option) {
            $option['description'] = StringUtils::replace(self::HELP_REPLACE, $option['description']);
            $option['display'] = $this->encodeDefaultValue($option['default']);
        }
    }
}
