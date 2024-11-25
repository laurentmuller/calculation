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

namespace App\Model;

use App\Traits\ExceptionContextTrait;

/**
 * Contains Http client error.
 */
class HttpClientError implements \JsonSerializable, \Stringable
{
    use ExceptionContextTrait;

    /**
     * @param int             $code      the error code
     * @param string          $message   the error message
     * @param \Throwable|null $exception the optional source exception
     */
    public function __construct(private readonly int $code, private string $message, private readonly ?\Throwable $exception = null)
    {
    }

    public function __toString(): string
    {
        return \sprintf('%d. %s', $this->code, $this->message);
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getException(): ?\Throwable
    {
        return $this->exception;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @psalm-return array{
     *      result: false,
     *      code: int,
     *      message: string,
     *      exception?: array{
     *              message: string,
     *              code: string|int,
     *              file: string,
     *              line: int,
     *              class: string,
     *              trace: string}
     *     }
     */
    public function jsonSerialize(): array
    {
        $result = [
            'result' => false,
            'code' => $this->code,
            'message' => $this->message,
        ];
        if ($this->exception instanceof \Exception) {
            $result['exception'] = $this->getExceptionContext($this->exception);
        }

        return $result;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }
}
