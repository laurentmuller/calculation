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

use App\Controller\AbstractController;
use App\Traits\JsonResponseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class JsonResponseTraitTest extends TestCase
{
    use JsonResponseTrait;

    public function testJsonException(): void
    {
        $exception = new \Exception('Invalid value');
        $response = $this->jsonException($exception);
        $this->validateResponse($response, [
            'result' => false,
            'message' => $exception->getMessage(),
        ]);
    }

    public function testJsonFalse(): void
    {
        $response = $this->jsonFalse();
        $this->validateResponse($response, ['result' => false]);
    }

    public function testJsonTrue(): void
    {
        $response = $this->jsonTrue();
        $this->validateResponse($response, ['result' => true]);
    }

    public function testWithController(): void
    {
        $controller = new class extends AbstractController {
            #[\Override]
            protected function json(mixed $data, int $status = 200, array $headers = [], array $context = []): JsonResponse
            {
                return new JsonResponse($data, $status, $headers, false);
            }

            #[\Override]
            public function jsonTrue(array $data = [], int $status = Response::HTTP_OK): JsonResponse
            {
                return parent::jsonTrue($data, $status);
            }
        };
        $response = $controller->jsonTrue();
        $this->validateResponse($response, ['result' => true]);
    }

    private function validateResponse(JsonResponse $response, array $expected): void
    {
        try {
            $content = $response->getContent();
            self::assertIsString($content);

            $actual = \json_decode(json: $content, associative: true, flags: \JSON_THROW_ON_ERROR);
            foreach ($expected as $key => $value) {
                self::assertArrayHasKey($key, $actual);
                self::assertSame($value, $actual[$key]);
            }
        } catch (\JsonException $e) {
            self::fail($e->getMessage());
        }
    }
}
