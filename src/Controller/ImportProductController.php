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

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Product;
use App\Excel\ExcelDocument;
use App\Form\FormHelper;
use App\Util\Utils;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\File;

/**
 * Controller to import products from an Excel Sheet.
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
    public function import(Request $request, EntityManagerInterface $manager, LoggerInterface $logger): Response
    {
        // create form
        $helper = $this->createImportHelper();
        $form = $helper->createForm();

        if ($this->handleRequestForm($request, $form)) {
            $data = $form->getData();
            $simulate = (bool) $data['simulate'];

            /** @var UploadedFile $file */
            $file = $data['file'];

            $categories = [];

            /** @var \App\Repository\CategoryRepository $categoryRepository */
            $categoryRepository = $manager->getRepository(Category::class);
            foreach ($categoryRepository->findAllByCode() as $category) {
                $categories[$category->getCode()] = $category;
            }
            $skipped = 0;
            $products = [];

            try {
                $reader = new Xlsx();
                $reader->setReadDataOnly(true);

                $workbook = $reader->load($file->getRealPath());
                $sheet = $workbook->setActiveSheetIndex(0);
                $rowIterator = $sheet->getRowIterator(2);
                foreach ($rowIterator as $row) {
                    $product = new Product();
                    $cellIterator = $row->getCellIterator();

                    foreach ($cellIterator as $cell) {
                        $value = $cell->getValue();
                        $column = $cell->getColumn();
                        if ($value) {
                            switch ($column) {
                                case 'A': // group
                                    // ignore
                                    break;

                                case 'B': // category
                                    if (\array_key_exists($value, $categories)) {
                                        $category = $categories[$value];
                                        $product->setCategory($category);
                                    } else {
                                        ++$skipped;
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

                // save
                if (!$simulate) {
                    if (!empty($products)) {
                        $manager->getConnection()->getConfiguration()->setSQLLogger(null);
                        $manager->beginTransaction();
                        $manager->createQuery('DELETE FROM ' . Product::class)->execute();
                        foreach ($products as $product) {
                            $manager->persist($product);
                        }
                        $manager->flush();
                        $manager->commit();
                    }
                }

                // save values to session
                $this->setSessionValue('product.import.simulate', $simulate);
            } catch (\Exception $e) {
                $message = $this->trans('product.import.failure');
                $context = [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ];
                $logger->error($message, $context);

                $data = [
                    'valid' => false,
                    'message' => $e->getMessage(),
                ];
            }

            return $this->render('product/product_import_result.html.twig', $data);
        }

        // display
        return $this->render('product/product_import_file.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function createImportHelper(): FormHelper
    {
        // create form
        $data = [
            'confirm' => false,
            'simulate' => $this->isSessionBool('product.import.simulate', true),
        ];

        // create form
        $helper = $this->createFormHelper('product.import.fields.', $data);

        // constraints
        $constraints = new File([
            'mimeTypes' => ExcelDocument::MIME_TYPE,
            'mimeTypesMessage' => $this->trans('import.error.mime_type'),
        ]);

        // fields
        $helper->field('file')
            ->updateOption('constraints', $constraints)
            ->updateAttribute('accept', ExcelDocument::MIME_TYPE)
            ->addFileType();

        $helper->field('simulate')
            ->help('product.import.simulate_help')
            ->updateHelpAttribute('class', 'ml-4 mb-2')
            ->notRequired()
            ->addCheckboxType();

        $helper->field('confirm')
            ->updateOption('mapped', false)
            ->updateRowAttribute('class', 'mb-0')
            ->updateAttribute('data-error', $this->trans('product.import.error.confirm'))
            ->addCheckboxType();

        return $helper;
    }

    /**
     * Checks if the given product is valid.
     */
    private function isValid(Product $product): bool
    {
        if (Utils::isString($product->getDescription()) && null !== $product->getCategory()) {
            return true;
        }

        return false;
    }
}
