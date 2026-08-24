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

final class PhpInfoServiceTest extends TestCase
{
    public function testMoveCoreModule(): void
    {
        $coreGroup = new Group(
            configs: new Items(),
        );
        $coreGroups = new Items([$coreGroup]);
        $coreModule = new Module('Core', $coreGroups);

        $generalGroup = new Group(
            configs: new Items(),
        );
        $generalGroups = new Items([$generalGroup]);
        $generalModule = new Module('General', $generalGroups);

        $info = new PhpInfo(\PHP_VERSION, new Items([$coreModule, $generalModule]));
        $service = new PhpInfoService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(3, $actual['modules']);
    }

    public function testMoveCoreModuleNoGeneral(): void
    {
        $coreGroup = new Group(
            configs: new Items(),
        );
        $coreGroups = new Items([$coreGroup]);
        $coreModule = new Module('Core', $coreGroups);

        $info = new PhpInfo(\PHP_VERSION, new Items([$coreModule]));
        $service = new PhpInfoService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(2, $actual['modules']);
    }

    public function testPhpInfo(): void
    {
        $configs = new Items([
            $this->createConfig(name: 'Config1', localValue: 'local1', masterValue: 'master1'),
            $this->createConfig(name: 'Config2', localValue: 'local2'),
            $this->createConfig(name: 'None', localValue: 'none'),
            $this->createConfig(name: 'UTF-8', localValue: '✘'),
            $this->createConfig(name: 'Color', localValue: '#000000'),
            $this->createConfig(name: 'FAKE_USER_NAME', localValue: 'fake'),
            $this->createConfig(name: 'No Value', localValue: 'no value'),
            $this->createConfig(name: 'Enabled Value', localValue: 'true'),
            $this->createConfig(name: 'Disabled Value', localValue: 'false'),
            $this->createConfig(name: 'Replace Middle', localValue: 'REMEMBERME=12345;FAKE'),
            $this->createConfig(name: 'Replace End', localValue: 'REMEMBERME=12345'),
        ]);
        $headings = $this->createHeadings();
        $group = new Group(
            configs: $configs,
            headings: $headings,
            name: 'Group',
            note: 'Note',
        );

        $groups = new Items([$group]);
        $module1 = new Module('module', $groups);
        $module2 = $this->createVariablesModule();

        $modules = new Items([
            $module1,
            $module2,
        ]);
        $info = new PhpInfo(\PHP_VERSION, $modules);

        $service = new PhpInfoService();
        $actual = $service->getPhpInfo($info);
        self::assertSame(\PHP_VERSION, $actual['version']);
        self::assertNotEmpty($actual['modules']);
    }

    public function testVariablesWithNoMatchName(): void
    {
        $configs = new Items([
            $this->createConfig(name: 'FAKE', localValue: 'local', masterValue: 'master'),
        ]);
        $group = new Group(
            configs: $configs,
        );
        $module = new Module('PHP Variables', new Items([$group]));
        $info = new PhpInfo(\PHP_VERSION, new Items([$module]));
        $service = new PhpInfoService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(2, $actual['modules']);
    }

    public function testVariablesWithoutGroup(): void
    {
        $module = new Module('PHP Variables', new Items());
        $info = new PhpInfo(\PHP_VERSION, new Items([$module]));
        $service = new PhpInfoService();
        $actual = $service->getPhpInfo($info);
        self::assertCount(1, $actual['modules']);
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
        return new Items([
            'Directive',
            'Local Value',
            'Master Value',
        ]);
    }

    private function createVariablesModule(): Module
    {
        $configs = new Items([
            $this->createConfig(name: '$_REQUEST[\'FAKE\']', localValue: 'local', masterValue: 'master'),
        ]);
        $group = new Group(
            configs: $configs,
        );
        $groups = new Items([$group]);

        return new Module('PHP Variables', $groups);
    }
}
