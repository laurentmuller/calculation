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
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Shared\Converter;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Abstract word document.
 */
abstract class AbstractWordDocument extends WordDocument
{
    use TranslatorTrait;

    private readonly CustomerInformation $customer;

    private readonly WordFooter $footer;

    private readonly WordHeader $header;

    private bool $printAddress;

    private readonly TranslatorInterface $translator;

    /**
     * @param AbstractController $controller the parent's controller
     */
    public function __construct(protected readonly AbstractController $controller)
    {
        parent::__construct();
        $this->translator = $this->controller->getTranslator();
        $this->customer = $this->controller->getApplicationService()->getCustomer();
        $this->printAddress = $this->controller->getUserService()->isPrintAddress();
        $this->header = new WordHeader($this);
        $this->footer = new WordFooter($this);
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
     * Set a value indicating if the customer address is printed.
     *
     * @psalm-api
     */
    public function setPrintAddress(bool $printAddress): static
    {
        $this->printAddress = $printAddress;

        return $this;
    }

    /**
     * Sets the title to be translated.
     *
     * @param string  $id         the message identifier (may also be an object that can be cast to string)
     * @param array   $parameters an array of parameters for the message
     * @param ?string $domain     the domain for the message or null to use the default
     */
    public function setTitleTrans(string $id, array $parameters = [], ?string $domain = null): static
    {
        $title = $this->trans($id, $parameters, $domain);

        return $this->setTitle($title);
    }

    /**
     * Add the default footer to the given section.
     */
    protected function addDefaultFooter(Section $section): static
    {
        $this->footer->setUrl($this->controller->getApplicationOwnerUrl())
            ->setName($this->controller->getApplicationName())
            ->output($section);

        return $this;
    }

    /**
     * Add the default header to the given section.
     */
    protected function addDefaultHeader(Section $section): static
    {
        $this->header->setPrintAddress($this->printAddress)
            ->setCustomer($this->customer)
            ->output($section);

        return $this;
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

    protected function initialize(): void
    {
        parent::initialize();
        $properties = $this->getDocInfo();
        $properties->setCategory($this->controller->getApplicationName());
        $user = $this->controller->getUserIdentifier();
        if (null !== $user) {
            $properties->setCreator($user);
        }
        $customer = $this->controller->getApplicationService()->getCustomerName();
        if (null !== $customer) {
            $properties->setCompany($customer);
        }
    }
}
