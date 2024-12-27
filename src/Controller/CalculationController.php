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

namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
use App\Entity\Calculation;
use App\Entity\CalculationState;
use App\Entity\Product;
use App\Form\Calculation\CalculationEditStateType;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Interfaces\SortModeInterface;
use App\Report\CalculationReport;
use App\Report\CalculationsReport;
use App\Repository\CalculationRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\CalculationService;
use App\Spreadsheet\CalculationDocument;
use App\Spreadsheet\CalculationsDocument;
use App\Table\CalculationTable;
use App\Table\DataQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for calculation entities.
 *
 * @template-extends AbstractEntityController<Calculation, CalculationRepository>
 */
#[AsController]
#[Route(path: '/calculation', name: 'calculation_')]
#[IsGranted(RoleInterface::ROLE_USER)]
class CalculationController extends AbstractEntityController
{
    public function __construct(CalculationRepository $repository, private readonly CalculationService $service)
    {
        parent::__construct($repository);
    }

    /**
     * Add a new calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[GetPost(path: '/add', name: 'add')]
    public function add(Request $request): Response
    {
        $item = new Calculation();
        $application = $this->getApplicationService();
        $state = $application->getDefaultState();
        if ($state instanceof CalculationState) {
            $item->setState($state);
        }
        $product = $application->getDefaultProduct();
        if ($product instanceof Product) {
            $item->addProduct($product, $application->getDefaultQuantity());
            $this->service->updateTotal($item);
        }

        return $this->editEntity($request, $item);
    }

    /**
     * Edit a copy (cloned) calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[GetPost(path: '/clone/{id}', name: 'clone', requirements: self::ID_REQUIREMENT)]
    public function clone(Request $request, Calculation $item): Response
    {
        $description = $this->trans('common.clone_description', ['%description%' => $item->getDescription()]);
        $state = $this->getApplicationService()->getDefaultState();
        $clone = $item->clone($state, $description);
        $parameters = [
            'title' => 'calculation.clone.title',
            'params' => ['id' => $item->getId()],
            'overall_below' => $this->isMarginBelow($clone),
        ];

        return $this->editEntity($request, $clone, $parameters);
    }

    /**
     * Delete a calculation.
     */
    #[GetDelete(path: '/delete/{id}', name: 'delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, Calculation $item, LoggerInterface $logger): Response
    {
        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a calculation.
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[GetPost(path: '/edit/{id}', name: 'edit', requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, Calculation $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the calculations to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'excel')]
    public function excel(): SpreadsheetResponse
    {
        $entities = $this->getEntities(['id' => SortModeInterface::SORT_DESC]);
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculation.list.empty');
        }
        $doc = new CalculationsDocument($this, $entities);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export a single calculation to a Spreadsheet document.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel/{id}', name: 'excel_id', requirements: self::ID_REQUIREMENT)]
    public function excelOne(Calculation $calculation): SpreadsheetResponse
    {
        $doc = new CalculationDocument($this, $calculation);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'index')]
    public function index(
        CalculationTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'calculation/calculation_table.html.twig');
    }

    /**
     * Export calculations to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no calculation is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(): PdfResponse
    {
        $entities = $this->getEntities([
            'editable' => SortModeInterface::SORT_DESC,
            'code' => SortModeInterface::SORT_ASC,
            'id' => SortModeInterface::SORT_DESC,
        ]);
        if ([] === $entities) {
            throw $this->createTranslatedNotFoundException('calculation.list.empty');
        }
        $doc = new CalculationsReport($this, $entities);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Export a single calculation to a PDF document.
     */
    #[Get(path: '/pdf/{id}', name: 'pdf_id', requirements: self::ID_REQUIREMENT)]
    public function pdfOne(Calculation $calculation): PdfResponse
    {
        $minMargin = $this->getMinMargin();
        $qrcode = $this->getQrCode($calculation);
        $doc = new CalculationReport($this, $calculation, $minMargin, $qrcode);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Show properties of a calculation.
     */
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(Calculation $item): Response
    {
        $parameters = [
            'min_margin' => $this->getMinMargin(),
            'duplicate_items' => $item->hasDuplicateItems(),
            'empty_items' => $item->hasEmptyItems(),
        ];

        return $this->showEntity($item, $parameters);
    }

    /**
     * Edit the state of a calculation.
     */
    #[GetPost(path: '/state/{id}', name: 'state', requirements: self::ID_REQUIREMENT)]
    public function state(Request $request, Calculation $item): Response
    {
        $form = $this->createForm(CalculationEditStateType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            $this->getRepository()->flush();

            return $this->redirectToDefaultRoute($request, $item);
        }

        $parameters = [
            'form' => $form,
            'item' => $item,
        ];
        $this->updateQueryParameters($request, $parameters, $item);

        return $this->render('calculation/calculation_state.html.twig', $parameters);
    }

    /**
     * @param Calculation $item
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['min_margin'] = $this->getMinMargin();
        $parameters['empty_items'] = $item->hasEmptyItems();
        $parameters['duplicate_items'] = $item->hasDuplicateItems();
        $parameters['overall_below'] = $this->isMarginBelow($item);
        $parameters['groups'] = $this->service->createGroupsFromCalculation($item);
        $parameters['editable'] = false;
        if ($item->isEditable()) {
            $parameters['editable'] = true;
            $parameters['group_index'] = $item->getGroupsCount();
            $parameters['category_index'] = $item->getCategoriesCount();
            $parameters['item_index'] = $item->getLinesCount();
        }

        return parent::editEntity($request, $item, $parameters);
    }

    /**
     * @psalm-param Calculation $item
     *
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    protected function saveToDatabase(EntityInterface $item): void
    {
        $this->service->updateTotal($item);
        parent::saveToDatabase($item);
    }

    /**
     * Gets the QR-code for the given calculation.
     */
    private function getQrCode(Calculation $calculation): string
    {
        if (!$this->getUserService()->isQrCode()) {
            return '';
        }

        return $this->generateUrl(
            'calculation_show',
            ['id' => $calculation->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );
    }

    private function isMarginBelow(Calculation $calculation): bool
    {
        return $this->getApplicationService()->isMarginBelow($calculation);
    }
}
