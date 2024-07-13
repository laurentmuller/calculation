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

namespace App\Tests\Spreadsheet;

use App\Controller\AbstractController;
use App\Entity\Group;
use App\Entity\GroupMargin;
use App\Spreadsheet\GroupsDocument;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

class GroupsDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRenderWithMargins(): void
    {
        $group = new Group();
        $group->setCode('Group');
        $group->addMargin(new GroupMargin())
            ->addMargin(new GroupMargin());

        $controller = $this->createMock(AbstractController::class);
        $document = new GroupsDocument($controller, [$group]);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderWithoutMargin(): void
    {
        $group = new Group();
        $group->setCode('Group');

        $controller = $this->createMock(AbstractController::class);
        $document = new GroupsDocument($controller, [$group]);
        $actual = $document->render();
        self::assertTrue($actual);
    }
}
