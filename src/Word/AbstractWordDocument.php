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

use App\Interfaces\DocumentHelperInterface;
use App\Model\CustomerInformation;
use App\Service\ApplicationService;
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

    public function __construct(protected readonly DocumentHelperInterface $helper)
    {
        parent::__construct();
        $this->customer = $this->helper->getCustomer();
        $this->header = new WordHeader($this);
        $this->footer = new WordFooter($this);
    }

    #[\Override]
    public function getTranslator(): TranslatorInterface
    {
        return $this->helper->getTranslator();
    }

    /**
     * Render this document.
     *
     * @return bool true if rendered successfully; false otherwise
     */
    abstract public function render(): bool;

    /**
     * Sets the title to be translated.
     *
     * @param string $id         the message identifier (may also be an object that can be cast to string)
     * @param array  $parameters an array of parameters for the message
     */
    public function setTranslatedTitle(string $id, array $parameters = []): static
    {
        return $this->setTitle($this->trans($id, $parameters));
    }

    /**
     * Add the default footer to the given section.
     */
    protected function addDefaultFooter(Section $section): static
    {
        $this->footer->setUrl(ApplicationService::OWNER_URL)
            ->setName(ApplicationService::APP_FULL_NAME)
            ->output($section);

        return $this;
    }

    /**
     * Add the default header to the given section.
     */
    protected function addDefaultHeader(Section $section): static
    {
        $this->header->setCustomer($this->customer)
            ->output($section);

        return $this;
    }

    /**
     * Create a section with the default header, footer, and margins.
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

    #[\Override]
    protected function initialize(): void
    {
        parent::initialize();
        $properties = $this->getDocInfo();
        $properties->setCategory(ApplicationService::APP_FULL_NAME);
        $user = $this->helper->getUserIdentifier();
        if (null !== $user) {
            $properties->setCreator($user);
        }
        $name = $this->helper->getCustomer()
            ->getName();
        if (null !== $name) {
            $properties->setCompany($name);
        }
    }
}
