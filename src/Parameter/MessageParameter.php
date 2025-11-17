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

namespace App\Parameter;

use App\Attribute\Parameter;
use App\Enums\MessagePosition;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Message (notification) parameter.
 */
class MessageParameter implements ParameterInterface
{
    #[Parameter('message_close', true)]
    private bool $close = true;

    #[Parameter('message_icon', true)]
    private bool $icon = true;

    #[Parameter('message_position', MessagePosition::BOTTOM_RIGHT)]
    private MessagePosition $position = MessagePosition::BOTTOM_RIGHT;

    #[Assert\DivisibleBy(1)]
    #[Assert\Range(min: 0, max: 5)]
    #[Parameter('message_progress', 1)]
    private int $progress = 1;

    #[Parameter('message_sub_title', false)]
    private bool $subTitle = false;

    #[Assert\DivisibleBy(1000)]
    #[Assert\Range(min: 1000, max: 5000)]
    #[Parameter('message_timeout', 4000)]
    private int $timeout = 4000;

    #[Parameter('message_title', true)]
    private bool $title = true;

    #[\Override]
    public static function getCacheKey(): string
    {
        return 'parameter_message';
    }

    public function getPosition(): MessagePosition
    {
        return $this->position;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isClose(): bool
    {
        return $this->close;
    }

    public function isIcon(): bool
    {
        return $this->icon;
    }

    public function isSubTitle(): bool
    {
        return $this->subTitle;
    }

    public function isTitle(): bool
    {
        return $this->title;
    }

    public function setClose(bool $close): self
    {
        $this->close = $close;

        return $this;
    }

    public function setIcon(bool $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function setPosition(MessagePosition $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function setSubTitle(bool $subTitle): self
    {
        $this->subTitle = $subTitle;

        return $this;
    }

    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    public function setTitle(bool $title): self
    {
        $this->title = $title;

        return $this;
    }
}
