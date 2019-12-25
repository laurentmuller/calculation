<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Utils;

/**
 * Extract the frames (and their duration) of a GIF.
 *
 * @version 1.5
 *
 * @see https://github.com/Sybio/GifFrameExtractor
 *
 * @author Sybio (Clément Guillemain  / @Sybio01)
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright Clément Guillemain
 */
class GifFrameExtractor implements \Countable
{
    /**
     * The file header informations.
     *
     * @var array
     */
    private $fileHeader;

    /**
     * The number of frames.
     *
     * @var int
     */
    private $frameCount;

    /**
     * The frames.
     *
     * @var array
     */
    private $frames;

    /**
     * The frame source informations,.
     *
     * @var array
     */
    private $frameSources;

    /**
     * The global data.
     *
     * @var array
     */
    private $globaldata;

    /**
     * The file handler.
     *
     * @var int
     */
    private $handle;

    /**
     * The maximum height.
     *
     * @var int
     */
    private $maxHeight;

    /**
     * The maximum width.
     *
     * @var int
     */
    private $maxWidth;

    /**
     * The variables.
     *
     * @var array
     */
    private $orgvars;

    /**
     * The reader pointer (position) in the file source.
     *
     * @var int
     */
    private $pointer;

    /**
     * The total duration.
     *
     * @var int
     */
    private $totalDuration;

    /**
     * Close the gif file.
     *
     * @return true on success or false on failure
     */
    public function closeFile(): bool
    {
        $result = true;
        if ($this->handle) {
            $result = \fclose($this->handle);
            $this->handle = 0;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->frameCount;
    }

    /**
     * Extract frames of a GIF file.
     *
     * @param string $filename       the GIF filename path
     * @param bool   $originalFrames true to get original frames (with transparent background)
     *
     * @return array the frames (image, x, y, width, height and duration)
     *
     * @throws \Exception:: if the GIF file name is not animated (contains only 1 frame)
     */
    public function extract($filename, $originalFrames = false): array
    {
        if (!self::isAnimatedGif($filename)) {
            throw new \Exception('The GIF image you are trying to explode is not animated !');
        }

        $this->reset();
        $this->parseFramesInfo($filename);
        $prevImg = null;

        for ($i = 0; $i < \count($this->frameSources); ++$i) {
            $this->frames[$i] = [];
            $this->frames[$i]['x'] = $this->frameSources[$i]['x'];
            $this->frames[$i]['y'] = $this->frameSources[$i]['y'];
            $this->frames[$i]['width'] = $this->frameSources[$i]['width'];
            $this->frames[$i]['height'] = $this->frameSources[$i]['height'];
            $this->frames[$i]['duration'] = $this->frameSources[$i]['duration'];

            $img = \imagecreatefromstring($this->fileHeader['gifheader'] . $this->frameSources[$i]['graphicsextension'] . $this->frameSources[$i]['imagedata'] . \chr(0x3b));

            if (!$originalFrames) {
                if ($i > 0) {
                    $prevImg = $this->frames[$i - 1]['image'];
                } else {
                    $prevImg = $img;
                }

                $sprite = \imagecreate($this->maxWidth, $this->maxHeight);
                \imagesavealpha($sprite, true);

                $transparent = \imagecolortransparent($prevImg);
                if ($transparent > -1 && \imagecolorstotal($prevImg) > $transparent) {
                    $actualTrans = \imagecolorsforindex($prevImg, $transparent);
                    \imagecolortransparent($sprite, \imagecolorallocate($sprite, $actualTrans['red'], $actualTrans['green'], $actualTrans['blue']));
                }

                if (1 === (int) $this->frameSources[$i]['disposal_method'] && $i > 0) {
                    \imagecopy($sprite, $prevImg, 0, 0, 0, 0, $this->maxWidth, $this->maxHeight);
                }

                \imagecopyresampled($sprite, $img, $this->frameSources[$i]['x'], $this->frameSources[$i]['y'], 0, 0, $this->maxWidth, $this->maxHeight, $this->maxWidth, $this->maxHeight);
                $img = $sprite;
            }

            $this->frames[$i]['image'] = $img;
        }

        return $this->frames;
    }

    /**
     * Get the extracted frames (image, x, y, width, height and duration).
     */
    public function getFrames(): array
    {
        return $this->frames;
    }

    /**
     * Gets the frame image at the given index.
     *
     * @param int $index the frame index
     *
     *  @return resource|null the image or null if index is out of range
     */
    public function getImage(int $index)
    {
        if ($index >= 0 && $index < $this->frameCount) {
            return $this->frames[$index]['image'];
        }

        return null;
    }

    /**
     * Get the total of all added frame duration.
     */
    public function getTotalDuration(): int
    {
        return $this->totalDuration;
    }

    /**
     * Check if a GIF file at a path is animated or not.
     *
     * @param string $filename the GIF file path
     *
     * @return bool true if animated
     */
    public static function isAnimatedGif(string $filename): bool
    {
        if (!($fh = @\fopen($filename, 'r'))) {
            return false;
        }

        $count = 0;
        while (!\feof($fh) && $count < 2) {
            $chunk = \fread($fh, 1024 * 100);
            $count += \preg_match_all('#\x00\x21\xF9\x04.{4}\x00(\x2C|\x21)#s', $chunk);
        }

        \fclose($fh);

        return $count > 1;
    }

    /**
     * Check if the next character is the given byte.
     *
     * @param int $byte the byte to compare to
     */
    private function checkByte(int $byte): bool
    {
        $result = \fgetc($this->handle) === \chr($byte);
        $this->seek();

        return $result;
    }

    /**
     * Get a section of the data from $start to $start + $length.
     *
     * @param int $start  the start offset
     * @param int $length the length
     */
    private function dataPart(int $start, int $length): string
    {
        \fseek($this->handle, $start);
        $data = \fread($this->handle, $length);
        $this->seek();

        return $data;
    }

    /**
     * Return the value of 2 ASCII chars.
     */
    private function dualByteVal(string $s): int
    {
        return \ord($s[1]) * 256 + \ord($s[0]);
    }

    /**
     * Forward the file pointer reader.
     *
     * @param int $length the length to move forward
     */
    private function forward(int $length): void
    {
        $this->pointer += $length;
        $this->seek();
    }

    /**
     * Get the image data bit.
     *
     * @param string $type      the image type
     * @param int    $byteIndex the byte index
     * @param int    $bitStart  the bit start
     * @param int    $bitLength the bit length
     *
     * @return number
     */
    private function getImageDataBit(string $type, int $byteIndex, int $bitStart, int $bitLength)
    {
        if ('ext' === $type) {
            return $this->readBits(\ord(\substr($this->frameSources[$this->frameCount]['graphicsextension'], $byteIndex, 1)), $bitStart, $bitLength);
        }

        // "dat"
        return $this->readBits(\ord(\substr($this->frameSources[$this->frameCount]['imagedata'], $byteIndex, 1)), $bitStart, $bitLength);
    }

    /**
     * Get the image data byte.
     *
     * @param string $type   the image type
     * @param int    $start  the start offset
     * @param int    $length the length
     */
    private function getImageDataByte(string $type, int $start, int $length): string
    {
        if ('ext' === $type) {
            return \substr($this->frameSources[$this->frameCount]['graphicsextension'], $start, $length);
        }

        // "dat"
        return \substr($this->frameSources[$this->frameCount]['imagedata'], $start, $length);
    }

    /**
     * Check if the end of the file is reached.
     */
    private function isEOF(): bool
    {
        if (false === \fgetc($this->handle)) {
            return true;
        }

        $this->seek();

        return false;
    }

    /**
     * Open the gif file.
     */
    private function openFile(string $filename): void
    {
        $this->handle = \fopen($filename, 'r');
        $this->pointer = 0;

//         $imageSize = \getimagesize($filename);
//         $this->gifWidth = $imageSize[0];
//         $this->gifHeight = $imageSize[1];
    }

    /**
     * Parse the application data of the frames.
     */
    private function parseApplicationData(): void
    {
        $startdata = $this->readByte(2);

        if ($startdata === \chr(0x21) . \chr(0xff)) {
            $start = $this->pointer - 2;
            $this->forward($this->readByteInt());
            $this->readDataStream($this->readByteInt());
            $this->fileHeader['applicationdata'] = $this->dataPart($start, $this->pointer - $start);
        } else {
            $this->rewind(2);
        }
    }

    /**
     * Parse the comment data of the frames.
     */
    private function parseCommentData(): void
    {
        $startdata = $this->readByte(2);

        if ($startdata === \chr(0x21) . \chr(0xfe)) {
            $start = $this->pointer - 2;
            $this->readDataStream($this->readByteInt());
            $this->fileHeader['commentdata'] = $this->dataPart($start, $this->pointer - $start);
        } else {
            $this->rewind(2);
        }
    }

    /**
     * Parse frame data string into an array.
     */
    private function parseFrameData(): void
    {
        $index = $this->frameCount;
        $this->frameSources[$index]['disposal_method'] = $this->getImageDataBit('ext', 3, 3, 3);
        $this->frameSources[$index]['user_input_flag'] = $this->getImageDataBit('ext', 3, 6, 1);
        $this->frameSources[$index]['transparent_color_flag'] = $this->getImageDataBit('ext', 3, 7, 1);
        $this->frameSources[$index]['duration'] = $this->dualByteVal($this->getImageDataByte('ext', 4, 2));
        $this->totalDuration += (int) $this->frameSources[$index]['duration'];
        $this->frameSources[$index]['transparent_color_index'] = \ord($this->getImageDataByte('ext', 6, 1));
        $this->frameSources[$index]['x'] = $this->dualByteVal($this->getImageDataByte('dat', 1, 2));
        $this->frameSources[$index]['y'] = $this->dualByteVal($this->getImageDataByte('dat', 3, 2));
        $this->frameSources[$index]['width'] = $this->dualByteVal($this->getImageDataByte('dat', 5, 2));
        $this->frameSources[$index]['height'] = $this->dualByteVal($this->getImageDataByte('dat', 7, 2));
        $this->frameSources[$index]['local_color_table_flag'] = $this->getImageDataBit('dat', 9, 0, 1);
        $this->frameSources[$index]['interlace_flag'] = $this->getImageDataBit('dat', 9, 1, 1);
        $this->frameSources[$index]['sort_flag'] = $this->getImageDataBit('dat', 9, 2, 1);
        $this->frameSources[$index]['color_table_size'] = 2 ** ($this->getImageDataBit('dat', 9, 5, 3) + 1) * 3;
        $this->frameSources[$index]['color_table'] = \substr($this->frameSources[$index]['imagedata'], 10, $this->frameSources[$index]['color_table_size']);
        $this->frameSources[$index]['lzw_code_size'] = \ord($this->getImageDataByte('dat', 10, 1));

        // Decoding
        $this->orgvars[$index]['transparent_color_flag'] = $this->frameSources[$index]['transparent_color_flag'];
        $this->orgvars[$index]['transparent_color_index'] = $this->frameSources[$index]['transparent_color_index'];
        $this->orgvars[$index]['duration'] = $this->frameSources[$index]['duration'];
        $this->orgvars[$index]['disposal_method'] = $this->frameSources[$index]['disposal_method'];
        $this->orgvars[$index]['x'] = $this->frameSources[$index]['x'];
        $this->orgvars[$index]['y'] = $this->frameSources[$index]['y'];

        // Updating the max width
        $width = $this->frameSources[$index]['width'];
        if ($this->maxWidth < $width) {
            $this->maxWidth = $width;
        }

        // Updating the max height
        $height = $this->frameSources[$index]['height'];
        if ($this->maxHeight < $height) {
            $this->maxHeight = $height;
        }
    }

    /**
     * Parse the frame informations contained in the GIF file.
     *
     * @param string $filename GIF filename path
     */
    private function parseFramesInfo(string $filename): void
    {
        $this->openFile($filename);
        $this->parseGifHeader();
        $this->parseGraphicsExtension(0);
        $this->parseApplicationData();
        $this->parseApplicationData();
        $this->parseFrameString(0);
        $this->parseGraphicsExtension(1);
        $this->parseCommentData();
        $this->parseApplicationData();
        $this->parseFrameString(1);

        while (!$this->checkByte(0x3b) && !$this->isEOF()) {
            $this->parseCommentData();
            $this->parseGraphicsExtension(2);
            $this->parseFrameString(2);
            $this->parseApplicationData();
        }
    }

    /**
     * Get the full frame string block.
     *
     * @param int $type the frame type
     */
    private function parseFrameString(int $type): void
    {
        if ($this->checkByte(0x2c)) {
            $start = $this->pointer;
            $this->forward(9);

            if (1 === $this->readBits(($byte = $this->readByteInt()), 0, 1)) {
                $this->forward(2 ** ($this->readBits($byte, 5, 3) + 1) * 3);
            }

            $index = $this->frameCount;
            $this->forward(1);
            $this->readDataStream($this->readByteInt());
            $this->frameSources[$index]['imagedata'] = $this->dataPart($start, $this->pointer - $start);

            switch ($type) {
                case 0:
                    $this->orgvars['hasgx_type_0'] = 0;
                    if (isset($this->globaldata['graphicsextension_0'])) {
                        $this->frameSources[$index]['graphicsextension'] = $this->globaldata['graphicsextension_0'];
                    } else {
                        $this->frameSources[$index]['graphicsextension'] = null;
                    }
                    unset($this->globaldata['graphicsextension_0']);
                    break;
                case 1:
                    if (isset($this->orgvars['hasgx_type_1']) && 1 === $this->orgvars['hasgx_type_1']) {
                        $this->orgvars['hasgx_type_1'] = 0;
                        $this->frameSources[$index]['graphicsextension'] = $this->globaldata['graphicsextension'];
                        unset($this->globaldata['graphicsextension']);
                    } else {
                        $this->orgvars['hasgx_type_0'] = 0;
                        $this->frameSources[$index]['graphicsextension'] = $this->globaldata['graphicsextension_0'];
                        unset($this->globaldata['graphicsextension_0']);
                    }
                    break;
            }
            $this->parseFrameData();
            ++$this->frameCount;
        }
    }

    /**
     * Parse the gif header.
     */
    private function parseGifHeader(): void
    {
        $this->forward(10);

        if (1 === $this->readBits(($byte = $this->readByteInt()), 0, 1)) {
            $this->forward(2);
            $this->forward(2 ** ($this->readBits($byte, 5, 3) + 1) * 3);
        } else {
            $this->forward(2);
        }

        $this->fileHeader['gifheader'] = $this->dataPart(0, $this->pointer);

        // Decoding
        $this->orgvars['gifheader'] = $this->fileHeader['gifheader'];
        $this->orgvars['background_color'] = $this->orgvars['gifheader'][11];
    }

    /**
     * Parse the graphic extension of the frames.
     *
     * @param int $type the frame type
     */
    private function parseGraphicsExtension(int $type): void
    {
        $startdata = $this->readByte(2);

        if ($startdata === \chr(0x21) . \chr(0xf9)) {
            $start = $this->pointer - 2;
            $this->forward($this->readByteInt());
            $this->forward(1);

            switch ($type) {
                case 2:
                    $this->frameSources[$this->frameCount]['graphicsextension'] = $this->dataPart($start, $this->pointer - $start);
                    break;
                case 1:
                    $this->orgvars['hasgx_type_1'] = 1;
                    $this->globaldata['graphicsextension'] = $this->dataPart($start, $this->pointer - $start);
                    break;
                case 0:
                    $this->orgvars['hasgx_type_0'] = 1;
                    $this->globaldata['graphicsextension_0'] = $this->dataPart($start, $this->pointer - $start);
                    break;
            }
        } else {
            $this->rewind(2);
        }
    }

    /**
     * Convert a byte to decimal.
     *
     * @param int $byte   the value to convert
     * @param int $start  the start offset
     * @param int $length the length
     *
     * @return number
     */
    private function readBits(int $byte, int $start, int $length)
    {
        $bin = \str_pad(\decbin($byte), 8, '0', STR_PAD_LEFT);
        $data = \substr($bin, $start, $length);

        return \bindec($data);
    }

    /**
     * Read the file from the beginning to byte count in binary.
     *
     * @param int $byteCount up to length number of bytes read
     */
    private function readByte(int $byteCount): string
    {
        $data = \fread($this->handle, $byteCount);
        $this->pointer += $byteCount;

        return $data;
    }

    /**
     * Read a byte and return ASCII value.
     */
    private function readByteInt(): int
    {
        $data = \fread($this->handle, 1);
        ++$this->pointer;

        return \ord($data);
    }

    /**
     * Read the data stream.
     *
     * @param int $firstLength the initial length to skip
     */
    private function readDataStream(int $firstLength): void
    {
        $this->forward($firstLength);
        $length = $this->readByteInt();

        if (0 !== $length) {
            while (0 !== $length) {
                $this->forward($length);
                $length = $this->readByteInt();
            }
        }
    }

    /**
     * Reset and clear this current object.
     */
    private function reset(): void
    {
        $this->closeFile();
        $this->totalDuration = $this->maxHeight = $this->maxWidth = $this->handle = $this->pointer = $this->frameCount = 0;
        $this->globaldata = $this->orgvars = $this->frames = $this->fileHeader = $this->frameSources = [];
    }

    /**
     * Rewind the file pointer reader.
     *
     * @param int $length the length to move backward
     */
    private function rewind(int $length): void
    {
        $this->pointer -= $length;
        $this->seek();
    }

    /**
     * Move the file pointer to it's current position.
     */
    private function seek(): void
    {
        \fseek($this->handle, $this->pointer);
    }
}
