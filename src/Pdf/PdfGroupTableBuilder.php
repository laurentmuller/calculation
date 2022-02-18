<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Pdf;

/**
 * Extends the PDF table builder by adding a group row when headers and/or new pages are
 * outputed.
 *
 * @author Laurent Muller
 */
class PdfGroupTableBuilder extends PdfTableBuilder
{
    /**
     * The group.
     */
    protected ?PdfGroup $group = null;

    /**
     * The group render listener.
     */
    protected ?PdfGroupListenerInterface $groupListener = null;

    /**
     * The outputing group state.
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
        if (parent::checkNewPage($height)) {
            $this->outputGroup();

            return true;
        }

        return false;
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
        return null !== $this->group ? $this->group->getStyle() : null;
    }

    /**
     * Output the group (if any).
     */
    public function outputGroup(): self
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
     *
     * @return self this instance
     */
    public function setGroup(PdfGroup $group, bool $output = true): self
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
     * Sets the group key.
     *
     * Do nothing if the new group key is equals to the existing group key.
     *
     * @param mixed $key    the new group key
     * @param bool  $output true to output the new group (if not empty)
     *
     * @return self this instance
     */
    public function setGroupKey($key, bool $output = true): self
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
    public function setGroupListener(?PdfGroupListenerInterface $groupListener): self
    {
        $this->groupListener = $groupListener;

        return $this;
    }

    /**
     * Sets the group style.
     */
    public function setGroupStyle(?PdfStyle $style): self
    {
        if (null !== $this->group) {
            $this->group->setStyle($style);
        }

        return $this;
    }
}
