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

use App\Enums\ImageExtension;
use App\Traits\ArrayTrait;
use App\Traits\MathTrait;
use App\Traits\SessionAwareTrait;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceMethodsSubscriberTrait;
use Symfony\Contracts\Service\ServiceSubscriberInterface;

/**
 * Service to generate and validate a captcha image.
 *
 * @psalm-type ComputeTextType = array{
 *     char: string,
 *     angle: int,
 *     height: int,
 *     width: int}
 */
class CaptchaImageService implements ServiceSubscriberInterface
{
    use ArrayTrait;
    use MathTrait;
    use ServiceMethodsSubscriberTrait;
    use SessionAwareTrait;

    /**
     * The default validation timeout in seconds (180 seconds = 3 minutes).
     */
    public const DEFAULT_TIME_OUT = 180;

    /**
     * The allowed characters.
     */
    private const ALLOWED_VALUES = 'abcdefghjklmnpqrstuvwxyz23456789';

    /**
     * The space between characters.
     */
    private const CHAR_SPACE = 3;

    /**
     * The base 64 image data prefix.
     */
    private const IMAGE_PREFIX = 'data:image/png;base64,';

    /**
     * The attribute name for the encoded image data.
     */
    private const KEY_DATA = 'captcha_data';

    /**
     * The attribute name for the captcha text.
     */
    private const KEY_TEXT = 'captcha_text';

    /**
     * The attribute name for the captcha timeout.
     */
    private const KEY_TIME = 'captcha_time';

    private int $timeout = self::DEFAULT_TIME_OUT;

    public function __construct(
        #[Autowire('%kernel.project_dir%/resources/fonts/captcha.ttf')]
        private readonly string $font
    ) {
    }

    /**
     * Remove captcha values from the session.
     */
    public function clear(): self
    {
        $this->removeSessionValues(
            self::KEY_TEXT,
            self::KEY_TIME,
            self::KEY_DATA
        );

        return $this;
    }

    /**
     * Generate a captcha image and save values to the session.
     *
     * @param bool $force  true to recreate an image, false to take previous created image (if any)
     * @param int  $length the number of characters to output
     * @param int  $width  the image width
     * @param int  $height the image height
     *
     * @return ?string the image encoded with base 64 or null if the image cannot be created
     *
     * @throws \Exception
     *
     * @psalm-param positive-int $length
     * @psalm-param positive-int $width
     * @psalm-param positive-int $height
     */
    public function generateImage(bool $force = false, int $length = 6, int $width = 150, int $height = 30): ?string
    {
        if (!$force && $this->validateTimeout() && $this->hasSessionValue(self::KEY_DATA)) {
            return $this->getSessionString(self::KEY_DATA);
        }

        $this->clear();
        $text = $this->generateRandomString($length);
        $image = $this->createImage($text, $width, $height);
        if (!$image instanceof ImageService) {
            return null;
        }

        $data = $this->encodeImage($image);
        $this->setSessionValues([
            self::KEY_TEXT => $text,
            self::KEY_DATA => $data,
            self::KEY_TIME => \time(),
        ]);

        return $data;
    }

    /**
     * Gets validation timeout in seconds.
     *
     * The default value is 180 seconds (3 minutes).
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Sets validation timeout in seconds.
     *
     * The minimum value allowed is 10 seconds.
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = \max($timeout, 10);

        return $this;
    }

    /**
     * Validate the timeout.
     */
    public function validateTimeout(): bool
    {
        $actual = \time();
        $last = $this->getSessionInt(self::KEY_TIME, 0);
        $delta = $actual - $last;

        return $delta <= $this->getTimeout();
    }

    /**
     * Validate the given token; ignoring case.
     */
    public function validateToken(?string $token): bool
    {
        if (null === $token || '' === $token) {
            return false;
        }
        $sessionToken = $this->getSessionString(self::KEY_TEXT);
        if (null === $sessionToken) {
            return false;
        }

        return StringUtils::equalIgnoreCase($token, $sessionToken);
    }

    /**
     * Compute the text layout.
     *
     * @throws \Exception
     */
    private function computeText(ImageService $image, float $size, string $font, string $text): array
    {
        return \array_map(function (string $char) use ($image, $size, $font): array {
            $angle = \random_int(-8, 8);
            [$width, $height] = $image->ttfSize($size, $angle, $font, $char);

            return [
                'char' => $char,
                'angle' => $angle,
                'width' => $width,
                'height' => $height,
            ];
        }, \str_split($text));
    }

    /**
     * Create an image.
     *
     * @throws \Exception
     *
     * @psalm-param positive-int $width
     * @psalm-param positive-int $height
     */
    private function createImage(string $text, int $width, int $height): ?ImageService
    {
        $image = ImageService::fromTrueColor($width, $height);
        if (!$image instanceof ImageService) {
            return null;
        }

        $this->drawBackground($image);
        $this->drawPoints($image, $width, $height);
        $this->drawLines($image, $width, $height);
        $this->drawText($image, $width, $height, $text);

        return $image;
    }

    /**
     * Draws the white background image.
     */
    private function drawBackground(ImageService $image): void
    {
        $color = $image->allocateWhite();
        if (!\is_int($color)) {
            return;
        }

        $image->fill($color);
    }

    /**
     * Draws horizontal gray lines in the background.
     *
     * @throws \Exception
     */
    private function drawLines(ImageService $image, int $width, int $height): void
    {
        $color = $image->allocate(195, 195, 195);
        if (!\is_int($color)) {
            return;
        }

        $lines = \random_int(3, 7);
        for ($i = 0; $i < $lines; ++$i) {
            $y1 = \random_int(0, $height);
            $y2 = \random_int(0, $height);
            $image->line(0, $y1, $width, $y2, $color);
        }
    }

    /**
     * Draws blue points in the background.
     *
     * @throws \Exception
     */
    private function drawPoints(ImageService $image, int $width, int $height): void
    {
        $color = $image->allocate(0, 0, 255);
        if (!\is_int($color)) {
            return;
        }

        $points = \random_int(300, 400);
        for ($i = 0; $i < $points; ++$i) {
            $x = \random_int(0, $width);
            $y = \random_int(0, $height);
            $image->setPixel($x, $y, $color);
        }
    }

    /**
     * Draws the image text.
     *
     * @throws \Exception
     */
    private function drawText(ImageService $image, int $width, int $height, string $text): void
    {
        $color = $image->allocateBlack();
        if (!\is_int($color)) {
            return;
        }

        $font = $this->font;
        $size = (int) ((float) $height * 0.7);
        /** @psalm-var non-empty-array<ComputeTextType> $items */
        $items = $this->computeText($image, $size, $font, $text);
        $textHeight = (int) $this->getColumnMax($items, 'height');
        $textWidth = (int) $this->getColumnSum($items, 'width') + (\count($items) - 1) * self::CHAR_SPACE;
        $x = \intdiv($width - $textWidth, 2);
        $y = \intdiv($height - $textHeight, 2) + $size;
        foreach ($items as $item) {
            $image->ttfText($size, $item['angle'], $x, $y, $color, $font, $item['char']);
            $x += $item['width'] + self::CHAR_SPACE;
        }
    }

    /**
     * Encodes the image with MIME base64.
     */
    private function encodeImage(ImageService $image): string
    {
        \ob_start();
        ImageExtension::PNG->saveImage($image);
        $buffer = (string) \ob_get_contents();
        \ob_end_clean();

        return self::IMAGE_PREFIX . \base64_encode($buffer);
    }

    /**
     * Generate a random string.
     */
    private function generateRandomString(int $length): string
    {
        $length = $this->validateRange($length, 2, \strlen(self::ALLOWED_VALUES));
        $result = \str_shuffle(self::ALLOWED_VALUES);

        return \substr($result, 0, $length);
    }
}
