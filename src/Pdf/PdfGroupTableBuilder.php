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

/**
 * Extends the PDF table builder by adding a group row when headers and/or new pages are output.
 */
class PdfGroupTableBuilder extends PdfTableBuilder
{
    /**
     * The group.
     */
    protected ?PdfGroup $group = null;

    /*
     * The output the group before header.
     */
    protected bool $groupBeforeHeader = false;

    /**
     * The group render listener.
     */
    protected ?PdfGroupListenerInterface $groupListener = null;

    /**
     * The outputting group state.
     */
    protected bool $inProgress = false;

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

    /**
     * {@inheritdoc}
     */
    public function checkNewPage(float $height): bool
    {
        if ($this->groupBeforeHeader && $this->repeatHeader) {
            $this->repeatHeader = false;
            if ($result = parent::checkNewPage($height)) {
                $this->outputGroup();
                $this->outputHeaders();
            }
            $this->repeatHeader = true;

            return $result;
        }

        if ($result = parent::checkNewPage($height)) {
            $this->outputGroup();
        }

        return $result;
    }

    /**
     * Gets the group.
     */
    public function getGroup(): ?PdfGroup
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
        return $this->group?->getStyle();
    }

    /**
     * Gets a value indicating if the group is output before header.
     */
    public function isGroupBeforeHeader(): bool
    {
        return $this->groupBeforeHeader;
    }

    /**
     * Output the group (if any).
     */
    public function outputGroup(): static
    {
        if ($this->group && $this->group->isKey() && !$this->inProgress) {
            $this->inProgress = true;
            if (!$this->groupListener instanceof PdfGroupListenerInterface || !$this->groupListener->onOutputGroup($this, $this->group)) {
                $this->group->output($this);
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
        if ($this->group && $this->group->getKey() !== $key) {
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
        $this->group?->setStyle($style);

        return $this;
    }
}
