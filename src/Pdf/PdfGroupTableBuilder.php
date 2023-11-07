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

namespace App\Pdf;

use App\Pdf\Events\PdfGroupEvent;
use App\Pdf\Interfaces\PdfGroupListenerInterface;

/**
 * Extends the PDF table builder by adding a group row when headers and/or new pages are output.
 */
class PdfGroupTableBuilder extends PdfTableBuilder
{
    /**
     * The current group.
     */
    private PdfGroup $group;

    /*
     * The output the group before header.
     */
    private bool $groupBeforeHeader = false;

    /**
     * The group render listener.
     */
    private ?PdfGroupListenerInterface $groupListener = null;

    /**
     * The outputting group state.
     */
    private bool $inProgress = false;

    /**
     * Constructor.
     *
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public function __construct(PdfDocument $parent, bool $fullWidth = true)
    {
        parent::__construct($parent, $fullWidth);
        $this->group = new PdfGroup();
    }

    public function checkNewPage(float $height): bool
    {
        if ($this->groupBeforeHeader && $this->isRepeatHeader()) {
            $this->setRepeatHeader(false);
            $result = parent::checkNewPage($height);
            if ($result) {
                $this->outputGroup();
                $this->outputHeaders();
            }
            $this->setRepeatHeader(true);

            return $result;
        }

        $result = parent::checkNewPage($height);
        if ($result) {
            $this->outputGroup();
        }

        return $result;
    }

    /**
     * Gets the group.
     */
    public function getGroup(): PdfGroup
    {
        return $this->group;
    }

    /**
     * Gets the group listener.
     */
    public function getGroupListener(): ?PdfGroupListenerInterface
    {
        return $this->groupListener;
    }

    /**
     * Gets the group style.
     */
    public function getGroupStyle(): ?PdfStyle
    {
        return $this->group->getStyle();
    }

    /**
     * Creates a new instance.
     *
     * @param PdfDocument $parent    the parent document to print in
     * @param bool        $fullWidth a value indicating if the table take all the printable width
     */
    public static function instance(PdfDocument $parent, bool $fullWidth = true): self
    {
        return new self($parent, $fullWidth);
    }

    /**
     * Gets a value indicating if the group is output before header.
     */
    public function isGroupBeforeHeader(): bool
    {
        return $this->groupBeforeHeader;
    }

    /**
     * Output the group.
     */
    public function outputGroup(): static
    {
        if ($this->group->hasKey() && !$this->inProgress) {
            $this->inProgress = true;
            if ($this->groupListener instanceof PdfGroupListenerInterface) {
                $event = new PdfGroupEvent($this, $this->group);
                if (!$this->groupListener->outputGroup($event)) {
                    $this->group->output($this);
                }
            }
            $this->inProgress = false;
        }

        return $this;
    }

    /**
     * Sets the group.
     *
     * Do nothing if the new group is equals to the existing group.
     *
     * @param PdfGroup $group  the group to set
     * @param bool     $output true to output the new group (if not empty)
     */
    public function setGroup(PdfGroup $group, bool $output = true): static
    {
        if ($this->group !== $group) {
            $this->group = $group;
            if ($output) {
                return $this->outputGroup();
            }
        }

        return $this;
    }

    /**
     * Sets a value indicating if the group is output before header.
     */
    public function setGroupBeforeHeader(bool $groupBeforeHeader): static
    {
        $this->groupBeforeHeader = $groupBeforeHeader;

        return $this;
    }

    /**
     * Sets the group key.
     *
     * Do nothing if the new group key is equals to the existing group key.
     *
     * @param mixed $key    the new group key
     * @param bool  $output true to output the new group (if not empty)
     */
    public function setGroupKey(mixed $key, bool $output = true): static
    {
        if ($this->group->getKey() !== $key) {
            $this->group->setKey($key);
            if ($output) {
                return $this->outputGroup();
            }
        }

        return $this;
    }

    /**
     * Sets the group listener.
     */
    public function setGroupListener(?PdfGroupListenerInterface $groupListener): static
    {
        $this->groupListener = $groupListener;

        return $this;
    }

    /**
     * Sets the group style.
     */
    public function setGroupStyle(?PdfStyle $style): static
    {
        $this->group->setStyle($style);

        return $this;
    }

    /**
     * Returns a value indicating if the group is currently output.
     */
    protected function isInProgress(): bool
    {
        return $this->inProgress;
    }

    /**
     * Sets a value indicating if the group is currently output.
     */
    protected function setInProgress(bool $inProgress): static
    {
        $this->inProgress = $inProgress;

        return $this;
    }
}
