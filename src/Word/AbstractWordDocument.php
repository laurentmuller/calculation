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

namespace App\Word;

use App\Controller\AbstractController;
use App\Traits\TranslatorTrait;
use App\Utils\FormatUtils;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract word document.
 */
abstract class AbstractWordDocument extends WordDocument
{
    use TranslatorTrait;

    private readonly TranslatorInterface $translator;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent's controller
     */
    public function __construct(protected readonly AbstractController $controller)
    {
        parent::__construct();
        $this->translator = $this->controller->getTranslator();
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Render this document.
     *
     * @return bool true if rendered successfully; false otherwise
     *
     * @throws \PhpOffice\PhpWord\Exception\Exception if an exception occurs
     */
    abstract public function render(): bool;

    /**
     * Sets the title to be translated.
     *
     * @param string $id     the title id (may also be an object that can be cast to string)
     * @param bool   $isUTF8 indicates if the title is encoded in ISO-8859-1 (false) or UTF-8 (true)
     */
    public function setTitleTrans(string $id, array $parameters = [], bool $isUTF8 = false, ?string $domain = null, ?string $locale = null): static
    {
        $title = $this->trans($id, $parameters, $domain, $locale);

        return $this->setTitle($title);
    }

    /**
     * Add the default footer to the given section.
     */
    protected function addDefaultFooter(Section $section): static
    {
        $cellStyle = ['size' => 9];
        $tableStyle = ['borderTopSize' => 1];
        $spaceBefore = Converter::pointToTwip(3);

        $footer = $section->addFooter();
        $row = $footer->addTable($tableStyle)->addRow();

        // page
        $page = 'Page {PAGE} / {NUMPAGES}';
        $leftCell = $row->addCell(4000);
        $leftCell->addPreserveText($page, $cellStyle, ['alignment' => JcTable::START, 'spaceBefore' => $spaceBefore]);

        // application
        $url = $this->controller->getApplicationOwnerUrl();
        $name = $this->controller->getApplicationName();
        $centerCell = $row->addCell(4000);
        $centerCell->addLink($url, $name, $cellStyle, ['alignment' => Jc::CENTER, 'spaceBefore' => $spaceBefore]);

        // date
        $date = FormatUtils::formatDateTime(new \DateTime());
        $rightCell = $row->addCell(4000);
        $rightCell->addText($date, $cellStyle, ['alignment' => Jc::END, 'spaceBefore' => $spaceBefore]);

        return $this;
    }

    /**
     * Add the default header to the given section.
     */
    protected function addDefaultHeader(Section $section): static
    {
        $title = $this->cleanText($this->getTitle());
        $customer = $this->cleanText($this->getCustomerName());
        if (null === $title && null === $customer) {
            return $this;
        }

        $tableStyle = ['borderBottomSize' => 1];
        $spaceAfter = Converter::pointToTwip(3);
        $cellStyle = ['size' => 10, 'bold' => true, 'spaceAfter' => Converter::pointToTwip(3)];

        $header = $section->addHeader();
        $row = $header->addTable($tableStyle)->addRow();

        // title
        $leftCell = $row->addCell(6000);
        $leftCell->addText((string) $title, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => $spaceAfter]);

        // customer
        $url = $this->getCustomerUrl();
        $rightCell = $row->addCell(6000);
        if (null === $url) {
            $rightCell->addText((string) $customer, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        } else {
            $rightCell->addLink($url, $customer, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        }

        return $this;
    }

    /**
     * Create a section with default header, footer and margins.
     *
     * @param array $style the section style
     */
    protected function createDefaultSection(array $style = []): Section
    {
        $defaultMargin = Converter::cmToTwip();
        $style = \array_merge([
            'marginTop' => Converter::cmToTwip(1.8),
            'marginBottom' => $defaultMargin,
            'marginLeft' => $defaultMargin,
            'marginRight' => $defaultMargin,
            'headerHeight' => $defaultMargin,
        ], $style);
        $section = $this->addSection($style);

        $this->addDefaultHeader($section)
            ->addDefaultFooter($section);

        return $section;
    }

    /**
     * {@inheritdoc}
     */
    protected function initialize(): void
    {
        parent::initialize();
        $properties = $this->getDocInfo();
        $properties->setSubject($this->controller->getApplicationName());
        if (null !== $user = $this->controller->getUserIdentifier()) {
            $properties->setCreator($user);
        }
        if (null !== $customer = $this->getCustomerName()) {
            $properties->setCompany($customer);
        }
    }

    private function cleanText(?string $str): ?string
    {
        return null !== $str ? \htmlspecialchars($str) : null;
    }

    private function getCustomerName(): ?string
    {
        return $this->controller->getApplication()->getCustomerName();
    }

    private function getCustomerUrl(): ?string
    {
        return $this->controller->getApplication()->getCustomerUrl();
    }
}
