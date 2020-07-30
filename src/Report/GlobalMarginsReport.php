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

namespace App\Report;

use App\Controller\AbstractController;
use App\Pdf\PdfColumn;
use App\Pdf\PdfTableBuilder;
use App\Util\Utils;

/**
 * Report for the list of global margins.
 *
 * @author Laurent Muller
 */
class GlobalMarginsReport extends AbstractReport
{
    /**
     * The global margins to render.
     *
     * @var \App\Entity\GlobalMargin[]
     */
    protected $globalMargins;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     */
    public function __construct(AbstractController $controller)
    {
        parent::__construct($controller);
        $this->setTitleTrans('globalmargin.list.title');
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // margins?
        $globalMargins = $this->globalMargins;
        $count = \count($globalMargins);
        if (0 === $count) {
            return false;
        }

        // sort
        Utils::sortFields($globalMargins, [
            'minimum',
            'maximum',
        ]);

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::right($this->trans('globalmargin.fields.minimum'), 50))
            ->addColumn(PdfColumn::right($this->trans('globalmargin.fields.maximum'), 50))
            ->addColumn(PdfColumn::right($this->trans('globalmargin.fields.margin'), 50))
            ->outputHeaders();

        // margins
        foreach ($globalMargins as $margin) {
            $table->startRow()
                ->add($this->localeAmount($margin->getMinimum()))
                ->add($this->localeAmount($margin->getMaximum()))
                ->add($this->localePercent($margin->getMargin()))
                ->endRow();
        }

        // count
        return $this->renderCount($count);
    }

    /**
     * Sets the global margins to render.
     *
     * @param \App\Entity\GlobalMargin[] $globalMargins
     */
    public function setGlobalMargins(array $globalMargins): self
    {
        $this->globalMargins = $globalMargins;

        return $this;
    }
}
