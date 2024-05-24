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

use App\Tests\TranslatorMockTrait;
use App\Traits\FlashMessageAwareTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Container\ContainerExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;

#[CoversClass(FlashMessageAwareTrait::class)]
#[CoversClass(TranslatorFlashMessageAwareTrait::class)]
class TranslatorFlashMessageAwareTraitTest extends AwareTraitTestCase
{
    use TranslatorFlashMessageAwareTrait;
    use TranslatorMockTrait;

    /**
     * @throws ContainerExceptionInterface
     */
    protected function setUp(): void
    {
        parent::setUp();

        $session = new Session();
        $request = new Request();
        $request->setSession($session);

        $requestStack = $this->getService(RequestStack::class);
        $requestStack->push($request);
        $this->setRequestStack($requestStack);

        $translator = $this->createTranslator();
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
