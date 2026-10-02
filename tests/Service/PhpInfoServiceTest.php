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

namespace App\Tests\Service;

use App\Service\PhpInfoService;
use PHPUnit\Framework\TestCase;
use STS\Phpinfo\Models\Config;
use STS\Phpinfo\Models\Group;
use STS\Phpinfo\Models\Module;
use STS\Phpinfo\PhpInfo;
use STS\Phpinfo\Support\Items;
use Symfony\Component\Cache\Adapter\NullAdapter;

final class PhpInfoServiceTest extends TestCase
{
    public function testConfigsEmpty(): void
    {
        $configs = $this->createItems();
        $coreGroup = new Group(configs: $configs);
        $coreGroups = $this->createItems($coreGroup);
        $coreModule = new Module('Core', $coreGroups);

        $info = new PhpInfo(\PHP_VERSION, $this->createItems($coreModule));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(0, $actual['modules']);
    }

    public function testMoveCoreModule(): void
    {
        $coreConfigs = $this->createItems(
            $this->createConfig(name: 'Default', localValue: 'local1', masterValue: 'master1'),
        );
        $coreGroup = new Group(configs: $coreConfigs);
        $coreGroups = $this->createItems($coreGroup);
        $coreModule = new Module('Core', $coreGroups);

        $generalConfigs = $this->createItems(
            $this->createConfig(name: 'Default', localValue: 'local1', masterValue: 'master1'),
        );
        $generalGroup = new Group(
            configs: $generalConfigs,
        );
        $generalGroups = $this->createItems($generalGroup);
        $generalModule = new Module('General', $generalGroups);

        $info = new PhpInfo(\PHP_VERSION, $this->createItems($coreModule, $generalModule));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(2, $actual['modules']);
    }

    public function testMoveCoreModuleNoGeneral(): void
    {
        $coreConfigs = $this->createItems(
            $this->createConfig(name: 'Default', localValue: 'local1', masterValue: 'master1'),
        );
        $coreGroup = new Group(configs: $coreConfigs);
        $coreGroups = $this->createItems($coreGroup);
        $coreModule = new Module('Core', $coreGroups);

        $info = new PhpInfo(\PHP_VERSION, $this->createItems($coreModule));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(1, $actual['modules']);
    }

    public function testPhpInfo(): void
    {
        $configs1 = $this->createItems(
            $this->createConfig(name: 'Default', localValue: 'local1', masterValue: 'master1'),
            $this->createConfig(name: 'Empty Master', localValue: 'local2', masterValue: ''),
            $this->createConfig(name: 'UTF-8', localValue: '✘'),
            $this->createConfig(name: 'Color', localValue: '#000000'),
            $this->createConfig(name: 'FAKE_USER_NAME', localValue: 'fake'),
            $this->createConfig(name: 'No Value', localValue: 'no value'),
            $this->createConfig(name: 'None Value', localValue: 'none'),
            $this->createConfig(name: 'Enabled Value', localValue: 'true'),
            $this->createConfig(name: 'Disabled Value', localValue: 'false'),
            $this->createConfig(name: 'Replace Middle', localValue: 'REMEMBERME=12345;FAKE'),
            $this->createConfig(name: 'Replace End', localValue: 'REMEMBERME=12345'),
        );
        $group1 = new Group(
            configs: $configs1,
            headings: $this->createHeadings(),
            name: 'Group',
            note: 'Note',
        );
        $groups1 = $this->createItems($group1);
        $module1 = new Module('calendar', $groups1);

        $module2 = $this->createVariablesModule();
        $modules = $this->createItems($module1, $module2);

        $info = new PhpInfo(\PHP_VERSION, $modules);
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertSame(\PHP_VERSION, $actual['version']);
        self::assertCount(2, $actual['modules']);
    }

    public function testVariablesWithNoMatchName(): void
    {
        $configs = $this->createItems(
            $this->createConfig(name: 'Fake', localValue: 'local', masterValue: 'master'),
        );
        $group = new Group(configs: $configs);
        $module = new Module('PHP Variables', $this->createItems($group));
        $info = new PhpInfo(\PHP_VERSION, $this->createItems($module));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(1, $actual['modules']);
    }

    public function testVariablesWithoutGroup(): void
    {
        $module = new Module('PHP Variables', $this->createItems());
        $info = new PhpInfo(\PHP_VERSION, $this->createItems($module));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(0, $actual['modules']);
    }

    public function testXdebugEmpty(): void
    {
        $configs = $this->createItems(
            $this->createConfig(name: 'Fake', localValue: 'local', masterValue: 'master'),
        );
        $group = new Group(configs: $configs);
        $groups = $this->createItems($group);
        $module = new Module('xdebug', $groups);

        $info = new PhpInfo(\PHP_VERSION, $this->createItems($module));
        $service = $this->createService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(1, $actual['modules']);
    }

    public function testXdebugFound(): void
    {
        $configs0 = $this->createItems(
            $this->createConfig(name: 'Entry1', localValue: 'local1', masterValue: 'master1'),
        );
        $group0 = new Group(configs: $configs0);
        $configs1 = $this->createItems(
            $this->createConfig(name: 'Directive', localValue: 'Local Value', masterValue: 'Master Value'),
            $this->createConfig(name: 'Entry2', localValue: 'local2', masterValue: 'master2'),
        );
        $group1 = new Group(configs: $configs1);
        $groups = $this->createItems($group0, $group1);
        $module = new Module('xdebug', $groups);

        $info = new PhpInfo(\PHP_VERSION, $this->createItems($module));
        $service = $this->createService();

        $actual = $service->getPhpInfo($info);
        self::assertCount(1, $actual['modules']);

        $actual = $actual['modules'][0]['groups'];
        self::assertCount(2, $actual);

        $actual = $actual[1]['configs'];
        self::assertCount(1, $actual);

        $actual = $actual[0];
        self::assertNull($actual['master']);
    }

    private function createConfig(
        string $name,
        string $localValue,
        ?string $masterValue = null
    ): Config {
        return new Config(
            name: $name,
            localValue: $localValue,
            masterValue: $masterValue,
            hasMasterValue: null !== $masterValue
        );
    }

    private function createHeadings(): Items
    {
        return $this->createItems('Directive', 'Local Value', 'Master Value');
    }

    private function createItems(mixed ...$values): Items
    {
        return new Items($values);
    }

    private function createService(): PhpInfoService
    {
        return new PhpInfoService(new NullAdapter());
    }

    private function createVariablesModule(): Module
    {
        $configs = $this->createItems(
            $this->createConfig(name: '$_REQUEST[\'FAKE\']', localValue: 'local', masterValue: 'master'),
        );
        $group = new Group(configs: $configs);
        $groups = $this->createItems($group);

        return new Module('PHP Variables', $groups);
    }
}
