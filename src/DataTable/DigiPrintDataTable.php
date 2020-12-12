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

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\DigiPrint;
use App\Repository\DigiPrintRepository;
use DataTables\DataTablesInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Twig\Environment;

/**
 * DigiPrint data table handler.
 *
 * @author Laurent Muller
 */
class DigiPrintDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = DigiPrint::class;

    /**
     * Constructor.
     *
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param DigiPrintRepository $repository  the repository to get entities
     * @param Environment         $environment the Twig environment to render actions cells
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, DigiPrintRepository $repository, Environment $environment)
    {
        parent::__construct($session, $datatables, $repository, $environment);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/digiprint.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['format' => DataColumn::SORT_ASC];
    }
}
