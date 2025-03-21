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

namespace App\Tests\Traits;

use App\Tests\KernelServiceTestCase;
use App\Tests\TranslatorMockTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

class TranslatorFlashMessageKernelServiceTest extends KernelServiceTestCase
{
    use TranslatorFlashMessageAwareTrait;
    use TranslatorMockTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $session = new Session();
        $request = new Request();
        $request->setSession($session);

        $requestStack = $this->getService(RequestStack::class);
        $requestStack->push($request);
        $this->setRequestStack($requestStack);

        $translator = $this->createMockTranslator();
        $this->setTranslator($translator);
    }

    public function testErrorTrans(): void
    {
        $id = 'error';
        $actual = $this->errorTrans($id);
        self::assertSame($id, $actual);
    }

    public function testInfoTrans(): void
    {
        $id = 'info';
        $actual = $this->infoTrans($id);
        self::assertSame($id, $actual);
    }

    public function testSuccessTrans(): void
    {
        $id = 'success';
        $actual = $this->successTrans($id);
        self::assertSame($id, $actual);
    }

    public function testWarningTrans(): void
    {
        $id = 'warning';
        $actual = $this->warningTrans($id);
        self::assertSame($id, $actual);
    }
}
