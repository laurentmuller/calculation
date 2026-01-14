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
use App\Traits\ImageSizeTrait;
use App\Traits\LoggerTrait;
use App\Traits\TranslatorTrait;
use Imagine\Gd\Imagine;
use Imagine\Image\Box;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Service to resize user profile image.
 */
class ImageResizer
{
    use ImageSizeTrait;
    use LoggerTrait;
    use TranslatorTrait;

    /**
     * The target image size.
     */
    public const IMAGE_SIZE = 192;

    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger
    ) {
    }

    #[\Override]
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Resize the image to the default size.
     *
     * @param string  $source the source image path
     * @param ?string $target the target image path or null to replace the source image
     *
     * @return bool true on success, false on error, or if the size is not a positive value
     */
    public function resize(string $source, ?string $target = null): bool
    {
        try {
            $target ??= $source;
            $targetSize = $this->getTargetSize($source);
            $options = ['format' => ImageExtension::PNG->value];
            $imagine = new Imagine();
            $imagine->open($source)
                ->resize($targetSize)
                ->save($target, $options);

            return true;
        } catch (\Exception $e) {
            $this->logException($e, $this->trans('user.image.failure'));

            return false;
        }
    }

    private function getTargetSize(string $filename): Box
    {
        $size = $this->getImageSize($filename);
        if ($size->isEmpty()) {
            throw new \InvalidArgumentException(\sprintf('Unable to get image size for the file "%s".', $filename));
        }
        $size = $size->resize(self::IMAGE_SIZE);

        return new Box($size->width, $size->height);
    }
}
