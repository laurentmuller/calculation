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
use App\Service\PhpInfoService;
use App\Spreadsheet\PhpIniDocument;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;

#[CoversClass(PhpIniDocument::class)]
class PhpIniDocumentTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testRender(): void
    {
        $data = [
            'First Group' => [
                'single' => 'single',
                'disabled' => 'disabled',
                'no value' => 'no value',
                'entry' => ['local' => 'local', 'master' => 'master'],
                'color' => ['local' => '#FF8000', 'master' => '#0000BB'],
            ],
            'Second Group' => [
                'other' => 'other',
            ],
        ];
        $document = $this->createDocument($data);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @throws Exception
     */
    public function testRenderEmpty(): void
    {
        $document = $this->createDocument([]);
        $actual = $document->render();
        self::assertTrue($actual);
    }

    /**
     * @psalm-param array<string, array<string, array{local: scalar, master: scalar}|scalar>> $data
     *
     * @throws Exception
     */
    private function createDocument(array $data): PhpIniDocument
    {
        $controller = $this->createMock(AbstractController::class);
        $service = $this->createMock(PhpInfoService::class);
        $service->expects(self::any())
            ->method('getVersion')
            ->willReturn(\PHP_VERSION);
        $service->expects(self::any())
            ->method('asArray')
            ->willReturn($data);

        return new PhpIniDocument($controller, $service);
    }
}
