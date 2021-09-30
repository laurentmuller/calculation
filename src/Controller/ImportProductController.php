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

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Spreadsheet\SpreadsheetResponse;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\RowCellIterator;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;

/**
 * Controller to import products from a Spreadsheet.
 *
 * @author Laurent Muller
 *
 * @IsGranted("ROLE_ADMIN")
 */
class ImportProductController extends AbstractController
{
    /**
     * Import products.
     *
     * @Route("/product/import", name="product_import")
     */
    public function invoke(Request $request, EntityManagerInterface $manager, LoggerInterface $logger): Response
    {
        // create form
        $form = $this->createImportForm();
        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();

            /** @var UploadedFile $file */
            $file = $data['file'];
            $simulate = (bool) $data['simulate'];

            $skipped = 0;
            $products = [];
            $categories = $this->getCategories($manager);

            try {
                // open
                $sheet = $this->loadSheet((string) $file->getRealPath());

                // run over rows
                $iterator = $sheet->getRowIterator(2);
                foreach ($iterator as $row) {
                    // read cells
                    $product = $this->createProduct($row->getCellIterator(), $categories);
                    if ($this->isValid($product)) {
                        $products[] = $product;
                    } else {
                        ++$skipped;
                    }
                }

                $data = [
                    'valid' => true,
                    'simulate' => $simulate,
                    'skipped' => $skipped,
                    'products' => \count($products),
                ];

                // update
                if (!$simulate && !empty($products)) {
                    $this->update($manager, $products);
                }

                // save values to session
                $this->setSessionValue('product.import.simulate', $simulate);
            } catch (\Exception $e) {
                $message = $this->trans('product.import.failure');
                $context = Utils::getExceptionContext($e);
                $logger->error($message, $context);

                $data = [
                    'valid' => false,
                    'message' => $e->getMessage(),
                ];
            }

            return $this->renderForm('product/product_import_result.html.twig', $data);
        }

        // display
        return $this->renderForm('product/product_import_file.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Creates the form to import products.
     */
    private function createImportForm(): FormInterface
    {
        // data
        $data = [
            'confirm' => false,
            'simulate' => $this->isSessionBool('product.import.simulate', true),
        ];

        // create helper
        $helper = $this->createFormHelper('product.import.fields.', $data);

        // file constraints
        $constraints = new File([
            'mimeTypes' => SpreadsheetResponse::MIME_TYPE_EXCEL,
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
        ]);

        // fields
        $helper->field('file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', SpreadsheetResponse::MIME_TYPE_EXCEL)
            ->addFileType();

        $helper->field('simulate')
            ->help('product.import.simulate_help')
            ->helpClass('ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->notMapped()
            ->helpClass('mb-0')
            ->updateAttribute('data-error', $this->trans('product.import.error.confirm'))
            ->addCheckboxType();

        return $helper->createForm();
    }

    /**
     * Creates a product for the given iterator.
     *
     * @param RowCellIterator $iterator   the cell iterator to get values
     * @param Category[]      $categories the categories
     *
     * @return Product the product
     */
    private function createProduct(RowCellIterator $iterator, array $categories): Product
    {
        $product = new Product();
        foreach ($iterator as $cell) {
            $value = $cell->getValue();
            $column = $cell->getColumn();
            if ($value) {
                switch ($column) {
                    case 'A': // group
                        // ignore
                        break;

                    case 'B': // category
                        if (\array_key_exists($value, $categories)) {
                            $product->setCategory($categories[$value]);
                        }
                        break;
                    case 'C': // name
                        $product->setDescription($value);
                        break;
                    case 'D': // complement
                        $description = $product->getDescription();
                        $description .= ' - ' . $value;
                        $product->setDescription($description);
                        break;

                    case 'E': // price
                        $product->setPrice($value);
                        break;

                    case 'F': // unit
                        $product->setUnit($value);
                        break;

                    case 'G': // supplier
                        $product->setSupplier($value);
                        break;
                }
            }
        }

        return $product;
    }

    /**
     * Gets the categories.
     *
     * @param EntityManagerInterface $manager the manager
     *
     * @return Category[]
     */
    private function getCategories(EntityManagerInterface $manager): array
    {
        $categories = [];

        /** @var \App\Repository\CategoryRepository $repository */
        $repository = $manager->getRepository(Category::class);

        /** @var \App\Entity\Category $category */
        foreach ($repository->findAll() as $category) {
            $categories[$category->getCode()] = $category;
        }

        return $categories;
    }

    /**
     * Checks if the given product is valid.
     */
    private function isValid(Product $product): bool
    {
        return Utils::isString($product->getDescription()) && null !== $product->getCategory();
    }

    /**
     * Opens the given Spreadsheet file path and select the first sheet.
     *
     * @param string $path the file path
     *
     * @return Worksheet the active sheet
     */
    private function loadSheet(string $path): Worksheet
    {
        $reader = new Xlsx();
        $reader->setReadDataOnly(true);
        $workbook = $reader->load($path);

        return $workbook->setActiveSheetIndex(0);
    }

    /**
     * Remove all old products and insert the new ones.
     *
     * @param EntityManagerInterface $manager  the manager
     * @param Product[]              $products the new products to insert
     */
    private function update(EntityManagerInterface $manager, array $products): void
    {
        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
        $manager->beginTransaction();

        // delete old products
        $manager->createQueryBuilder()->delete(Product::class)
            ->getQuery()->execute();

        // add new products
        foreach ($products as $product) {
            $manager->persist($product);
        }

        $manager->flush();
        $manager->commit();
    }
}
