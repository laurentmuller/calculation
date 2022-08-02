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

use App\Traits\SessionAwareTrait;
use App\Util\ImageHandler;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Symfony\Contracts\Service\ServiceSubscriberTrait;

/**
 * Service to generate and validate a captcha image.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class CaptchaImageService implements ServiceSubscriberInterface
{
    use ServiceSubscriberTrait;
    use SessionAwareTrait;

    /**
     * The allowed characters.
     */
    private const ALLOWED_VALUES = 'abcdefghijklmnopqrstuvwxyz0123456789';

    /**
     * The space between characters.
     */
    private const CHAR_SPACE = 3;

    /**
     * The font path and name.
     */
    private const FONT_PATH = '/resources/fonts/captcha.ttf';

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

    /**
     * The maximum validation timeout in seconds (3 minutes).
     */
    private const MAX_TIME_OUT = 180;

    /**
     * The font file name.
     */
    private readonly string $font;

    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $project_dir
    ) {
        $this->font = $project_dir . self::FONT_PATH;
    }

    /**
     * Remove captcha values from the session.
     */
    public function clear(): self
    {
        $this->removeSessionValues([self::KEY_TEXT, self::KEY_TIME, self::KEY_DATA]);

        return $this;
    }

    /**
     * Generate a captcha image and save values to the session.
     *
     * @param bool $force  true to recreate an image, false to take the previous created image (if any)
     * @param int  $length the number of characters to output
     * @param int  $width  the image width
     * @param int  $height the image height
     *
     * @return string|null the image encoded with the base 64 or null if the image canot be created
     *
     * @throws \Exception
     */
    public function generateImage(bool $force = false, int $length = 6, int $width = 150, int $height = 30): ?string
    {
        // not force and valid?
        if (!$force && $this->validateTimeout() && $this->hasSessionValue(self::KEY_DATA)) {
            return (string) $this->getSessionString(self::KEY_DATA);
        }

        // clear previous values
        $this->clear();

        // text
        $text = $this->generateRandomString($length);

        // image
        if (null !== $image = $this->createImage($text, $width, $height)) {
            // encode image
            $data = $this->encodeImage($image);

            // save
            $this->setSessionValues([
                self::KEY_TEXT => $text,
                self::KEY_DATA => $data,
                self::KEY_TIME => \time(),
            ]);

            return $data;
        }

        return null;
    }

    /**
     * Validate the timeout.
     */
    public function validateTimeout(): bool
    {
        $actual = \time();
        $last = (int) $this->getSessionInt(self::KEY_TIME, 0);
        $delta = $actual - $last;

        return $delta <= self::MAX_TIME_OUT;
    }

    /**
     * Validate the given token; ignoring case.
     */
    public function validateToken(?string $token): bool
    {
        return $token && 0 === \strcasecmp($token, (string) $this->getSessionString(self::KEY_TEXT, ''));
    }

    /**
     * Compute the text layout.
     *
     * @return array the text layout. Each entry is an array of 4 elements with the following values:<br><br>
     *               <table class="table table-bordered" border="1" cellpadding="5" style="border-collapse: collapse;">
     *               <tr>
     *               <th>Key</th>
     *               <th>Content</th>
     *               </tr>
     *               <tr>
     *               <td>'char'</td>
     *               <td>The character to output.</td>
     *               </tr>
     *               <tr>
     *               <td>'angle'</td>
     *               <td>The angle in degrees.</td>
     *               </tr>
     *               <tr>
     *               <td>'width'</td>
     *               <td>The character width.</td>
     *               </tr>
     *               <tr>
     *               <td>'height'</td>
     *               <td>The character height.</td>
     *               </tr>
     *               </table>
     *
     * @throws \Exception
     */
    private function computeText(ImageHandler $image, float $size, string $font, string $text): array
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
     */
    private function createImage(string $text, int $width, int $height): ?ImageHandler
    {
        // create image
        if (null === $image = ImageHandler::fromTrueColor($width, $height)) {
            return null;
        }

        // draw
        $this->drawBackground($image)
            ->drawPoints($image, $width, $height)
            ->drawLines($image, $width, $height)
            ->drawText($image, $width, $height, $text);

        return $image;
    }

    /**
     * Draws the white background image.
     */
    private function drawBackground(ImageHandler $image): self
    {
        $color = $image->allocateWhite();
        if (\is_int($color)) {
            $image->fill($color);
        }

        return $this;
    }

    /**
     * Draws horizontal gray lines in the background.
     *
     * @throws \Exception
     */
    private function drawLines(ImageHandler $image, int $width, int $height): self
    {
        $color = $image->allocate(195, 195, 195);
        if (\is_int($color)) {
            $lines = \random_int(3, 7);
            for ($i = 0; $i < $lines; ++$i) {
                $y1 = \random_int(0, $height);
                $y2 = \random_int(0, $height);
                $image->line(0, $y1, $width, $y2, $color);
            }
        }

        return $this;
    }

    /**
     * Draws blue points in the background.
     *
     * @throws \Exception
     */
    private function drawPoints(ImageHandler $image, int $width, int $height): self
    {
        $color = $image->allocate(0, 0, 255);
        if (\is_int($color)) {
            $points = \random_int(300, 400);
            for ($i = 0; $i < $points; ++$i) {
                $x = \random_int(0, $width);
                $y = \random_int(0, $height);
                $image->setPixel($x, $y, $color);
            }
        }

        return $this;
    }

    /**
     * Draws the image text.
     *
     * @throws \Exception
     */
    private function drawText(ImageHandler $image, int $width, int $height, string $text): self
    {
        // font and color
        $font = $this->font;
        $color = $image->allocateBlack();
        if (\is_int($color)) {
            // get layout
            $size = (int) ($height * 0.7);
            $items = $this->computeText($image, $size, $font, $text);

            // get position
            $textHeight = 0;
            $textWidth = -self::CHAR_SPACE;
            /** @psalm-var array $item */
            foreach ($items as $item) {
                $textWidth += (int) $item['width'] + self::CHAR_SPACE;
                $textHeight = \max($textHeight, (int) $item['height']);
            }
            $x = \intdiv($width - $textWidth, 2);
            $y = \intdiv($height - $textHeight, 2) + $size;

            // draw
            /** @psalm-var array $item */
            foreach ($items as $item) {
                $char = (string) $item['char'];
                $angle = (float) $item['angle'];
                $width = (float) $item['width'];
                $image->ttfText($size, $angle, (int) $x, $y, $color, $font, $char);
                $x += $width + self::CHAR_SPACE;
            }
        }

        return $this;
    }

    /**
     * Encodes the image with MIME base64.
     */
    private function encodeImage(ImageHandler $image): string
    {
        // save
        \ob_start();
        $image->toPng();
        $buffer = (string) \ob_get_contents();
        \ob_end_clean();

        // encode
        return self::IMAGE_PREFIX . \base64_encode($buffer);
    }

    /**
     * Generate a random string.
     */
    private function generateRandomString(int $length): string
    {
        $length = \min(\max($length, 2), \strlen(self::ALLOWED_VALUES));
        $result = \str_shuffle(self::ALLOWED_VALUES);

        return \substr($result, 0, $length);
    }
}
