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
     *
     * @var PdfGroup
     */
    protected $group;

    /**
     * The group render listener.
     *
     * @var PdfGroupListenerInterface|null
     */
    protected $groupListener;

    /**
     * The outputing group state.
     *
     * @var bool
     */
    private $inProgress = false;

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
     * Gets the group.
     *
     * @return PdfGroup
     */
    public function getGroup(): ?PdfGroup
    {
        return $this->group;
    }

    /**
     * Gets the group listener.
     *
     * @return \App\Pdf\PdfGroupListenerInterface
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
     * Output the group (if any).
     *
     * @return self this instance
     */
    public function outputGroup(): self
    {
        if ($this->group->isKey() && !$this->inProgress) {
            $this->inProgress = true;
            if (!$this->groupListener || !$this->groupListener->onOutputGroup($this, $this->group)) {
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
     *
     * @param PdfGroupListenerInterface $groupListener the listener to set
     */
    public function setGroupListener(?PdfGroupListenerInterface $groupListener): self
    {
        $this->groupListener = $groupListener;

        return $this;
    }

    /**
     * Sets the group style.
     *
     * @param PdfStyle $style the new group style
     *
     * @return self this instance
     */
    public function setGroupStyle(?PdfStyle $style): self
    {
        $this->group->setStyle($style);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @see \App\Pdf\PdfTableBuilder::checkNewPage()
     */
    protected function checkNewPage(float $height): bool
    {
        if (parent::checkNewPage($height)) {
            $this->outputGroup();

            return true;
        }

        return false;
    }
}
