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

use App\Attribute\GetRoute;
use App\Attribute\PdfRoute;
use App\Attribute\WordRoute;
use App\Interfaces\RoleInterface;
use App\Interfaces\SortModeInterface;
use App\Pdf\Events\PdfLabelTextEvent;
use App\Pdf\Interfaces\PdfLabelTextListenerInterface;
use App\Pdf\PdfLabelDocument;
use App\Report\FontAwesomeReport;
use App\Report\HtmlColorsReport;
use App\Report\HtmlReport;
use App\Report\MemoryImageReport;
use App\Repository\CustomerRepository;
use App\Response\PdfResponse;
use App\Response\WordResponse;
use App\Service\FontAwesomeImageService;
use App\Service\FontAwesomeService;
use App\Service\PdfLabelService;
use App\Utils\StringUtils;
use App\Word\HtmlDocument;
use fpdf\Enums\PdfFontStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/test', name: 'test_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestExportController extends AbstractController
{
    /**
     * Output a report with HTML and Boostrap colors.
     */
    #[GetRoute(path: '/colors', name: 'colors')]
    public function exportColors(): PdfResponse
    {
        return $this->renderPdfDocument(new HtmlColorsReport($this));
    }

    /**
     * Output a report with Fontawesome images.
     */
    #[GetRoute(path: '/fontawesome', name: 'fontawesome')]
    public function exportFontAwesome(FontAwesomeImageService $service): Response
    {
        return $this->renderPdfDocument(new FontAwesomeReport($this, $service));
    }

    /**
     * Export a report label.
     */
    #[GetRoute(path: '/label', name: 'label')]
    public function exportLabel(CustomerRepository $repository, PdfLabelService $service): PdfResponse
    {
        $listener = new class implements PdfLabelTextListenerInterface {
            #[\Override]
            public function drawLabelText(PdfLabelTextEvent $event): bool
            {
                if ($event->index !== $event->lines - 1 && $event->index > 2) {
                    return false;
                }

                if ('' === $event->text) {
                    return true;
                }

                $parent = $event->parent;
                $font = $parent->getCurrentFont();
                $parent->setFont(style: PdfFontStyle::BOLD);
                $parent->cell($event->width, $event->height, $event->text);
                $font->apply($parent);

                return true;
            }
        };

        $label = $service->get('5161');
        $report = new PdfLabelDocument($label);
        $report->setLabelBorder(true)
            ->setLabelTextListener($listener)
            ->setTitle(\sprintf('Etiquette - Avery %s', $label->name));

        $sortField = $repository->getSortField(CustomerRepository::NAME_COMPANY_FIELD);
        /** @phpstan-var \App\Entity\Customer[] $customers */
        $customers = $repository->createDefaultQueryBuilder()
            ->orderBy($sortField, SortModeInterface::SORT_ASC)
            ->setMaxResults(29)
            ->getQuery()
            ->getResult();

        foreach ($customers as $customer) {
            $values = \array_filter([
                $customer->getCompany(),
                $customer->getFullName(),
                StringUtils::NEW_LINE,
                $customer->getAddress(),
                $customer->getZipCity(),
            ]);
            $text = \implode(StringUtils::NEW_LINE, $values);
            $report->addLabel($text);
        }

        return $this->renderPdfDocument($report);
    }

    /**
     * Output a report with memory images.
     */
    #[GetRoute(path: '/memory', name: 'memory')]
    public function exportMemoryImage(
        #[Autowire('%kernel.project_dir%/public/images/logo/logo-customer-148x148.png')]
        string $logoFile,
        #[Autowire('%kernel.project_dir%/public/images/icons/favicon-144x144.png')]
        string $iconFile,
        #[Autowire('%kernel.project_dir%/public/images/screenshots/home_light.png')]
        string $screenshotFile,
        FontAwesomeService $service
    ): PdfResponse {
        $report = new MemoryImageReport(
            $this,
            $logoFile,
            $iconFile,
            $screenshotFile,
            $service
        );

        return $this->renderPdfDocument($report);
    }

    /**
     * Export an HTML page to PDF.
     */
    #[PdfRoute]
    public function exportPdf(): PdfResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $report = new HtmlReport($this, $content);
        $report->setTitle($this->trans('test.html'));

        return $this->renderPdfDocument($report);
    }

    /**
     * Export an HTML page to Word.
     */
    #[WordRoute]
    public function exportWord(): WordResponse
    {
        $content = $this->renderView('test/html_report.html.twig');
        $doc = new HtmlDocument($this, $content);
        $doc->setTranslatedTitle('test.html');

        return $this->renderWordDocument($doc);
    }
}
