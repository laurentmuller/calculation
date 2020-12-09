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

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\DigiPrint;
use App\Entity\DigiPrintItem;
use App\Pdf\PdfColumn;
use App\Pdf\PdfGroupTableBuilder;
use App\Util\FormatUtils;
use Doctrine\Common\Collections\Collection;

/**
 * Report for the list of digiprints.
 *
 * @author Laurent Muller
 */
class DigiPrintsReport extends AbstractArrayReport
{
    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param DigiPrint[]        $entities   the entities to export
     */
    public function __construct(AbstractController $controller, array $entities, bool $grouped = true)
    {
        parent::__construct($controller, $entities);
    }

    /**
     * {@inheritdoc}
     */
    protected function doRender(array $entities): bool
    {
        //title
        $this->SetTitle('Tarif d\'impression d\'affiches Digiprint XXL Eco solvants 720 dpi Blueback 120 gm2');

        // new page
        $this->AddPage();

        // create table
        $columns = [
            PdfColumn::left('Description', 50),
            PdfColumn::right('Minimum', 30, true),
            PdfColumn::right('Maximum', 30, true),
            PdfColumn::right('Prix', 30, true),
        ];

        $table = new PdfGroupTableBuilder($this);
        $table->addColumns($columns)
            ->outputHeaders();

        /** @var DigiPrint $entity */
        foreach ($entities as $entity) {
            $table->setGroupKey($entity->getDisplay());
            $this->outputType($entity->getItemPrices(), $table, 'Prix de base');
            $this->outputType($entity->getItemBacklits(), $table, 'Supplément pour Backlit');
            $this->outputType($entity->getItemReplicatings(), $table, 'Supplément pour repiquage 1 couleur noir ou blanc');
        }

        // count
        return $this->renderCount(\count($entities));
    }

    /**
     * Output the given items.
     *
     * @param Collection|DigiPrintItem[] $items the items to output
     * @param PdfGroupTableBuilder       $table the table to render
     * @param string                     $title the items title
     */
    private function outputType(Collection $items, PdfGroupTableBuilder $table, string $title): void
    {
        $count = $items->count();
        if (0 === $count) {
            return;
        }

        // contains within this page?
        $table->checkNewPage($count * self::LINE_HEIGHT);

        /** @var DigiPrintItem $item */
        foreach ($items as $item) {
            $table->startRow()
                ->add($title)
                ->add(FormatUtils::formatInt($item->getMinimum()))
                ->add(FormatUtils::formatInt($item->getMaximum()))
                ->add(FormatUtils::formatAmount($item->getAmount()))
                ->endRow();
            $title = null;
        }
    }
}
