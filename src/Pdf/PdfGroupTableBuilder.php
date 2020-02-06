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
     * @var IPdfGroupListener|null
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
     * @return \App\Pdf\PdfGroup
     */
    public function getGroup(): PdfGroup
    {
        return $this->group;
    }

    /**
     * Gets the group listener.
     *
     * @return \App\Pdf\IPdfGroupListener
     */
    public function getGroupListener(): ?IPdfGroupListener
    {
        return $this->groupListener;
    }

    /**
     * Output the group (if any).
     *
     * @return self this instance
     */
    public function outputGroup(): self
    {
        if ($this->group->isName() && !$this->inProgress) {
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
     * @param \App\Pdf\PdfGroup $group  the group to set
     * @param bool              $output true to output the new group (if not empty)
     *
     * @return self this instance
     */
    public function setGroup(PdfGroup $group, bool $output = true): self
    {
        $this->group = $group;
        if ($output) {
            return $this->outputGroup();
        }

        return $this;
    }

    /**
     * Sets the group listener.
     *
     * @param \App\Pdf\IPdfGroupListener $groupListener the listener to set
     *
     * @return self this instance
     */
    public function setGroupListener(?IPdfGroupListener $groupListener): self
    {
        $this->groupListener = $groupListener;

        return $this;
    }

    /**
     * Sets the group name.
     *
     * @param string $name   the new group name
     * @param bool   $output true to output the new group (if not empty)
     *
     * @return self this instance
     */
    public function setGroupName(string $name, bool $output = true): self
    {
        $this->group->setName($name);
        if ($output) {
            return $this->outputGroup();
        }

        return $this;
    }

    /**
     * Sets the group style.
     *
     * @param \App\Pdf\PdfStyle $style The new group style
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
