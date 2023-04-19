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
use App\Model\CustomerInformation;
use App\Traits\TranslatorTrait;
use Doctrine\Inflector\Rules\Word;
use PhpOffice\PhpWord\Element\Row;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use PhpOffice\PhpWord\SimpleType\Jc;
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
     * @param string  $id         the message id (may also be an object that can be cast to string)
     * @param array   $parameters an array of parameters for the message
     * @param ?string $domain     the domain for the message or null to use the default
     * @param ?string $locale     the locale or null to use the default
     */
    public function setTitleTrans(string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): static
    {
        $title = $this->trans($id, $parameters, $domain, $locale);

        return $this->setTitle($title);
    }

    /**
     * Add the default footer to the given section.
     */
    protected function addDefaultFooter(Section $section): static
    {
        $footer = new WordFooter();
        $footer->setUrl($this->controller->getApplicationOwnerUrl())
            ->setName($this->controller->getApplicationName())
            ->output($section);

        return $this;
    }

    /**
     * Add the default header to the given section.
     */
    protected function addDefaultHeader(Section $section): static
    {
        $customer = $this->controller->getApplication()->getCustomer();
        if (null === $this->getTitle() && null === $customer->getName()) {
            return $this;
        }

        $tableStyle = ['borderBottomSize' => 1];
        $header = $section->addHeader();
        $row = $header->addTable($tableStyle)->addRow();
        if ($this->controller->getUserService()->isPrintAddress()) {
            return $this->outputAddressHeader($row, $customer);
        }

        return $this->outputDefaultHeader($row, $customer);
    }

    /**
     * Create a section with the default header, footer and margins.
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
        if (null !== $customer = $this->controller->getApplication()->getCustomerName()) {
            $properties->setCompany($customer);
        }
    }

    private function cleanText(?string $str): string
    {
        return null !== $str ? \htmlspecialchars($str) : '';
    }

    private function outputAddressHeader(Row $row, CustomerInformation $customer): static
    {
        // style
        $spaceAfter = Converter::pointToTwip(3);
        $cellStyle = ['size' => 8, 'bold' => true];

        // customer
        $url = $customer->getUrl();
        $name = $this->cleanText($customer->getName());
        $leftCell = $row->addCell(4000);
        if (null === $url) {
            $leftCell->addText($name, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => 0]);
        } else {
            $leftCell->addLink($url, $name, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => 0]);
        }
        // address
        $cellStyle['bold'] = false;
        $address = $this->cleanText($customer->getAddress());
        $leftCell->addText($address, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => 0]);
        // zip and city
        $zipCity = $this->cleanText($customer->getZipCity());
        $leftCell->addText($zipCity, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => $spaceAfter]);

        // title
        $cellStyle['size'] = 10;
        $cellStyle['bold'] = true;
        $title = $this->cleanText($this->getTitle());
        $centerCell = $row->addCell(4000);
        $centerCell->addText($title, $cellStyle, ['alignment' => Jc::CENTER]);

        // phone
        $cellStyle['size'] = 8;
        $cellStyle['bold'] = false;
        $rightCell = $row->addCell(4000);
        $phone = $this->cleanText('Tél. : ' . (string) $customer->getPhone());
        $rightCell->addText($phone, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => 0]);
        // fax
        $fax = $this->cleanText('Fax : ' . (string) $customer->getFax());
        $rightCell->addText($fax, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => 0]);
        // email
        $email = $this->cleanText($customer->getEmail());
        if ('' !== $email) {
            $rightCell->addLink('mailto:' . $email, $email, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        } else {
            $rightCell->addText($email, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        }

        return $this;
    }

    private function outputDefaultHeader(Row $row, CustomerInformation $customer): static
    {
        // style
        $spaceAfter = Converter::pointToTwip(3);
        $cellStyle = ['size' => 8, 'bold' => true, 'spaceAfter' => $spaceAfter];

        // title
        $title = $this->cleanText($this->getTitle());
        $leftCell = $row->addCell(6000);
        $leftCell->addText($title, $cellStyle, ['alignment' => Jc::START, 'spaceAfter' => $spaceAfter]);

        // customer
        $url = $customer->getUrl();
        $name = $this->cleanText($customer->getName());
        $rightCell = $row->addCell(6000);
        if (null === $url) {
            $rightCell->addText($name, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        } else {
            $rightCell->addLink($url, $name, $cellStyle, ['alignment' => Jc::END, 'spaceAfter' => $spaceAfter]);
        }

        return $this;
    }
}
