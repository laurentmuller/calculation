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

namespace App\Model;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Contains application informations.
 *
 * @author Laurent Muller
 */
class ApplicationInfo
{
    private string $description;
    private string $name;
    private string $ownerCity;
    private string $ownerName;
    private string $ownerUrl;
    private string $version;

    /**
     * Constructor.
     */
    public function __construct(ParameterBagInterface $parameter)
    {
        $this->name = $this->getParameter($parameter, 'app_name');
        $this->version = $this->getParameter($parameter, 'app_version');
        $this->ownerName = $this->getParameter($parameter, 'app_owner');
        $this->ownerUrl = $this->getParameter($parameter, 'app_owner_url');
        $this->ownerCity = $this->getParameter($parameter, 'app_owner_city');
        $this->description = $this->getParameter($parameter, 'app_description');
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameAndVersion(): string
    {
        return \sprintf('%s v%s', $this->name, $this->version);
    }

    public function getOwnerCity(): string
    {
        return $this->ownerCity;
    }

    public function getOwnerName(): string
    {
        return $this->ownerName;
    }

    public function getOwnerUrl(): string
    {
        return $this->ownerUrl;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    private function getParameter(ParameterBagInterface $parameter, string $name): string
    {
        /** @psalm-var string $value */
        $value = $parameter->get($name);

        return $value;
    }
}
