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
 *     default: array|scalar|null,
 *     display: string,
 *     arguments: string}
 * @psalm-type OptionType = array{
 *     name: string,
 *     shortcut: string,
 *     name_shortcut: string,
 *     accept_value: bool,
 *     is_value_required: bool,
 *     is_multiple: bool,
 *     description: string,
 *     default: array|scalar|null,
 *     display: string,
 *     arguments: string}
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
class CommandService implements \Countable
{
    /**
     * The group name for commands without name space.
     */
    public const GLOBAL_GROUP = '_global';

    private const HELP_REPLACE = [
        // development
        '/development/public/index.php' => 'bin/console',
        // production
        '/calculation/public/index.php' => 'bin/console',
        // local development
        '/index.php/command/execute' => 'bin/console',
        '/index.php/command/pdf' => 'bin/console',
        '/index.php/command' => 'bin/console',
        // classes
        '<info>' => '<span class="text-success">',
        '<error>' => '<span class="text-danger">',
        '<comment>' => '<span class="text-secondary">',
        '<fg=yellow>' => '<span class="text-secondary">',
        '</info>' => '</span>',
        '</error>' => '</span>',
        '</comment>' => '</span>',
        '</>' => '</span>',
        // line break
        "\n" => '<br>',
    ];

    private const HREF_REPLACE = [
        '/(<href=)(.*?)>(.*?)(<\/>)/m' => '<a href=\"$2\">$3</a>',
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
     * Gets the number of commands.
     *
     * @throws InvalidArgumentException
     */
    public function count(): int
    {
        return \count($this->getCommands());
    }

    /**
     * Execute the given command.
     *
     * @param string $command         the command name to execute
     * @param array  $parameters      the command parameters
     * @param bool   $catchExceptions sets whether to catch exceptions or not during commands execution
     * @param bool   $catchErrors     sets whether to catch errors or not during commands execution
     *
     * @return CommandResult the result of the execution
     *
     * @throws \Exception if the running command fail. Bypass when <code>$catchExceptions</code> is <code>false</code>.
     */
    public function execute(
        string $command,
        array $parameters = [],
        bool $catchExceptions = true,
        bool $catchErrors = false,
    ): CommandResult {
        $parameters = ['command' => $command] + $parameters;
        $input = new ArrayInput($parameters);
        $output = new BufferedOutput();

        $application = new Application($this->kernel);
        $application->setCatchExceptions($catchExceptions);
        $application->setCatchErrors($catchErrors);
        $application->setAutoExit(false);
        $status = $application->run($input, $output);
        $content = $output->fetch();
        unset($application);

        return new CommandResult($status, $content);
    }

    /**
     * Gets the first command.
     *
     * @throws InvalidArgumentException
     */
    public function first(): array
    {
        $commands = $this->getCommands();

        return \reset($commands);
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

            /** @psalm-var ContentType $decoded */
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

                    $name = $command['name'];
                    $this->updateCommand($command);

                    return [$name => $command] + $carry;
                },
                []
            );
            \ksort($result);

            return $result;
        });
    }

    /**
     * Gets all command names grouped by name space.
     *
     * @param string $root the name of the group for commands without name space
     *
     * @return array<string, string[]>
     *
     * @throws InvalidArgumentException
     */
    public function getGroupedNames(string $root = self::GLOBAL_GROUP): array
    {
        $groups = [];
        $names = $this->getNames();
        foreach ($names as $name) {
            $values = \explode(':', $name);
            $group = 1 === \count($values) ? $root : $values[0];
            $groups[$group][] = $name;
        }

        return $groups;
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
     * @psalm-param ArgumentType $argument
     */
    private function getArgumentHelp(array $argument): string
    {
        $values = [];
        if ($argument['is_required']) {
            $values[] = '<span class="text-danger">required</span>';
        }
        $display = $argument['display'];
        if ('[]' === $display || $argument['is_array']) {
            $values[] = '<span class="text-secondary">multiple values allowed</span>';
        } elseif ('' !== $display) {
            $values[] = \sprintf('default: <span class="text-secondary">%s</span>', $display);
        }
        if ([] === $values) {
            return '';
        }

        return \sprintf('(%s)', \implode(', ', $values));
    }

    /**
     * @psalm-param OptionType $option
     */
    private function getOptionHelp(array $option): string
    {
        $values = [];
        if ($option['is_value_required']) {
            $values[] = '<span class="text-danger">required</span>';
        }
        $display = $option['display'];
        if ('[]' === $display || $option['is_multiple']) {
            $values[] = '<span class="text-secondary">multiple values allowed</span>';
        } elseif ('' !== $display) {
            $values[] = \sprintf('default: <span class="text-secondary">%s</span>', $display);
        }
        if ([] === $values) {
            return '';
        }

        return \sprintf('(%s)', \implode(', ', $values));
    }

    private function getOptionNameAndShortcut(string $name, string $shortcut): string
    {
        $format = '' === $shortcut ? '%4s%s' : '%s, %s';

        return \sprintf($format, $shortcut, $name);
    }

    private function replaceHelp(string $help): string
    {
        if (!StringUtils::isString($help)) {
            return $help;
        }

        $help = StringUtils::pregReplace(self::HREF_REPLACE, $help);

        return StringUtils::replace(self::HELP_REPLACE, $help);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @phpstan-param array $command
     */
    private function updateCommand(array &$command): void
    {
        $command['help'] = $command['help'] === $command['description'] ? '' : $this->replaceHelp($command['help']);
        if (0 === \count($command['definition']['arguments'])) {
            $command['usage'] = [\sprintf('%s [options]', $command['name'])];
        } else {
            $command['usage'] = StringUtils::pregReplace(self::USAGE_REPLACE, $command['usage']);
        }
        foreach ($command['definition']['arguments'] as &$argument) {
            $argument['description'] = $this->replaceHelp($argument['description']);
            $argument['display'] = $this->encodeDefaultValue($argument['default']);
            $argument['arguments'] = $this->getArgumentHelp($argument);
        }
        foreach ($command['definition']['options'] as &$option) {
            $option['description'] = $this->replaceHelp($option['description']);
            $option['display'] = $this->encodeDefaultValue($option['default']);
            $option['arguments'] = $this->getOptionHelp($option);
            $option['name_shortcut'] = $this->getOptionNameAndShortcut($option['name'], $option['shortcut']);
        }
    }
}
