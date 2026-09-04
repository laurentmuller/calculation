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

use App\Constants\CacheAttributes;
use App\Traits\CacheKeyTrait;
use App\Traits\EnablementValueTrait;
use App\Utils\StringUtils;
use STS\Phpinfo\Info;
use STS\Phpinfo\Models\Config;
use STS\Phpinfo\Models\Group;
use STS\Phpinfo\Models\Module;
use STS\Phpinfo\PhpInfo;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Service to get PHP information.
 *
 * @phpstan-type EntryType = array{
 *     value: string,
 *     type: self::TYPE_*
 * }
 * @phpstan-type ConfigType = array{
 *     name: string,
 *     local: EntryType,
 *     master: EntryType|null
 * }
 * @phpstan-type GroupType = array{
 *     name: string|null,
 *     note: string|null,
 *     headers: string[]|null,
 *     configs: ConfigType[]
 * }
 * @phpstan-type ModuleType = array{
 *     name: string,
 *     groups: GroupType[],
 *     url: string|null,
 *     size: int
 * }
 * @phpstan-type InfoType = array{
 *     version: string,
 *     modules: ModuleType[]
 * }
 */
class PhpInfoService
{
    use CacheKeyTrait;
    use EnablementValueTrait;

    public const int TYPE_COLOR = 0;
    public const int TYPE_DISABLED = 1;
    public const int TYPE_ENABLED = 2;
    public const int TYPE_NO_VALUE = 3;
    public const int TYPE_NONE_VALUE = 4;
    public const int TYPE_REDACTED = 5;
    public const int TYPE_UNDEFINED = -1;

    private const string NO_VALUE = 'No value';
    private const string NONE_VALUE = 'None';
    private const string REDACTED = '********';
    private const array  REDACTED_NAMES = [
        'APP_SECRET',
        'DATABASE_URL',
        'MAILER_DSN',
        'REMEMBERME',
        '_KEY',
        '_PASSWORD',
        '_PROFILE_TOKEN',
        '_SESSION_ID',
        '_USER_NAME',
    ];
    private const array SEARCH_KEYS = [
        'CALCULATION_SESSION_ID=',
        'MAIN_AUTH_PROFILE_TOKEN=',
        'REMEMBERME=',
    ];
    private const string URL_INFO = 'https://www.php.net/manual/en/book.%s.php';

    public function __construct(
        #[Target(CacheAttributes::CACHE_SYMFONY)]
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return InfoType
     */
    public function getPhpInfo(?PhpInfo $info = null): array
    {
        $info ??= Info::capture();

        return [
            'version' => $info->version(),
            'modules' => $this->parseModules($info),
        ];
    }

    private function convertValue(?string $value): string
    {
        if (!StringUtils::isString($value)) {
            return self::NO_VALUE;
        }
        if (StringUtils::equalIgnoreCase(\trim($value, '()'), self::NONE_VALUE)) {
            return self::NONE_VALUE;
        }
        $value = $this->replaceKeysValue($value);
        $value = \mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');

        return \str_replace(['✘ ', '✔ ', '⊕'], '', $value);
    }

    /**
     * @param array<int, ModuleType> $modules
     */
    private function findKeyModule(array $modules, string $name): ?int
    {
        return \array_find_key(
            $modules,
            static fn (array $module): bool => StringUtils::equalIgnoreCase($name, $module['name'])
        );
    }

    private function getModuleUrl(string $name): ?string
    {
        return $this->cache->get(
            $this->cleanKey('php-url-' . $name),
            fn (): ?string => $this->loadModuleUrl($name)
        );
    }

    private function isNoneValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase(self::NONE_VALUE, $value);
    }

    private function isNoValue(string $value): bool
    {
        return StringUtils::equalIgnoreCase(self::NO_VALUE, $value);
    }

    private function isRedacted(string $name): bool
    {
        return \array_any(
            self::REDACTED_NAMES,
            static fn (string $key): bool => StringUtils::containsIgnoreCase($name, $key)
        );
    }

    private function loadModuleUrl(string $name): ?string
    {
        $url = \sprintf(self::URL_INFO, $name);
        if (CurlService::instance()->isValidUrl($url)) {
            return $url;
        }

        return null;
    }

    /**
     * @param ModuleType[] $modules
     */
    private function moveCoreModule(array &$modules): void
    {
        $offsetCore = $this->findKeyModule($modules, 'Core');
        if (null === $offsetCore) {
            return;
        }
        $offsetGeneral = $this->findKeyModule($modules, 'General');
        if (null === $offsetGeneral) {
            return;
        }
        $module = \array_splice($modules, $offsetCore, 1);
        \array_splice($modules, $offsetGeneral + 1, 0, $module);
    }

    /**
     * @return ConfigType
     */
    private function parseConfig(Config $config): array
    {
        return [
            'name' => $config->name(),
            'local' => $this->parseConfigLocal($config),
            'master' => $this->parseConfigMaster($config),
        ];
    }

    /**
     * @return EntryType
     */
    private function parseConfigLocal(Config $config): array
    {
        return $this->parseValue($config->name(), $config->localValue());
    }

    /**
     * @return EntryType|null
     */
    private function parseConfigMaster(Config $config): ?array
    {
        if ($config->hasMasterValue()) {
            return $this->parseValue($config->name(), $config->masterValue());
        }

        return null;
    }

    /**
     * @return ConfigType[]
     */
    private function parseConfigs(Group $group): array
    {
        return \array_map(
            $this->parseConfig(...),
            $group->configs()->toArray()
        );
    }

    /**
     * @return GroupType
     */
    private function parseGroup(Group $group): array
    {
        return [
            'name' => StringUtils::trim($group->name()),
            'note' => StringUtils::trim($group->note()),
            'headers' => $this->parseHeaders($group),
            'configs' => $this->parseConfigs($group),
        ];
    }

    /**
     * @return GroupType[]
     */
    private function parseGroups(Module $module): array
    {
        return \array_map(
            $this->parseGroup(...),
            $module->groups()->toArray()
        );
    }

    /**
     * @return string[]|null
     */
    private function parseHeaders(Group $group): ?array
    {
        return $group->hasHeadings() ? $group->headings()->toArray() : null;
    }

    /**
     * @return ModuleType
     */
    private function parseModule(Module $module): array
    {
        $module = [
            'name' => $module->name(),
            'groups' => $this->parseGroups($module),
            'url' => $this->getModuleUrl($module->name()),
        ];
        $module['size'] = \array_reduce(
            $module['groups'],
            static fn (int $size, array $group): int => $size + \count($group['configs']),
            0
        );

        if (StringUtils::equalIgnoreCase('PHP Variables', $module['name'])) {
            return $this->parseVariables($module);
        }

        return $module;
    }

    /**
     * @return ModuleType[]
     */
    private function parseModules(PhpInfo $info): array
    {
        $modules = \array_map(
            $this->parseModule(...),
            $info->modules()->toArray()
        );

        // move Core module after General module
        $this->moveCoreModule($modules);

        // remove empty modules
        return \array_filter($modules, static fn (array $module): bool => 0 !== \count($module['groups']));
    }

    /**
     * @return EntryType
     */
    private function parseValue(string $name, ?string $value): array
    {
        $value = $this->convertValue($value);
        if (StringUtils::pregMatch('/#[\\da-f]{6}/i', $value, $matches)) {
            return [
                'value' => $matches[0],
                'type' => self::TYPE_COLOR,
            ];
        }
        if ($this->isDisabledValue($value)) {
            return [
                'value' => StringUtils::capitalize($value),
                'type' => self::TYPE_DISABLED,
            ];
        }
        if ($this->isEnabledValue($value)) {
            return [
                'value' => StringUtils::capitalize($value),
                'type' => self::TYPE_ENABLED,
            ];
        }
        if ($this->isNoValue($value)) {
            return [
                'value' => self::NO_VALUE,
                'type' => self::TYPE_NO_VALUE,
            ];
        }
        if ($this->isNoneValue($value)) {
            return [
                'value' => self::NONE_VALUE,
                'type' => self::TYPE_NONE_VALUE,
            ];
        }
        if ($this->isRedacted($name)) {
            return [
                'value' => self::REDACTED,
                'type' => self::TYPE_REDACTED,
            ];
        }

        return [
            'value' => $value,
            'type' => self::TYPE_UNDEFINED,
        ];
    }

    /**
     * @param ModuleType $module
     *
     * @return ModuleType
     */
    private function parseVariables(array $module): array
    {
        if (1 !== \count($module['groups'])) {
            return $module;
        }

        $groups = [];
        $group = $module['groups'][0];
        $pattern = '/\\$_(.*)\\[\'(.*)\']/';
        foreach ($group['configs'] as $config) {
            if (!StringUtils::pregMatch($pattern, $config['name'], $matches)) {
                return $module;
            }
            $name = $matches[1];
            $groups[$name] ??= [
                'name' => $name,
                'note' => null,
                'headers' => $group['headers'],
                'configs' => [],
            ];
            $groups[$name]['configs'][] = [
                'name' => $matches[2],
                'local' => $config['local'],
                'master' => $config['master'],
            ];
        }
        $module['groups'] = $this->sortGroups($groups);

        return $module;
    }

    private function replaceKeysValue(string $value): string
    {
        foreach (self::SEARCH_KEYS as $key) {
            $pos = \stripos($value, $key);
            if (false === $pos) {
                continue;
            }
            $pos += \strlen($key);
            $end = \stripos($value, ';', $pos);
            if (false === $end) {
                $value = \substr($value, 0, $pos) . self::REDACTED;
            } else {
                $value = \substr($value, 0, $pos) . self::REDACTED . \substr($value, $end);
            }
        }

        return $value;
    }

    /**
     * @param GroupType[] $groups
     *
     * @return GroupType[]
     */
    private function sortGroups(array $groups): array
    {
        foreach ($groups as &$group) {
            \uasort($group['configs'], static fn (array $a, array $b): int => $a['name'] <=> $b['name']);
        }

        return \array_values($groups);
    }
}
