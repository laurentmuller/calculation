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

namespace App\Parameter;

use App\Constant\CacheAttributes;
use App\Entity\User;
use App\Entity\UserProperty;
use App\Model\CustomerInformation;
use App\Traits\ArrayTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains user parameters (preferences).
 *
 * @extends AbstractParameters<UserProperty>
 */
class UserParameters extends AbstractParameters
{
    use ArrayTrait;

    public function __construct(
        #[Target(CacheAttributes::CACHE_USER)]
        CacheInterface $cache,
        EntityManagerInterface $manager,
        private readonly Security $security,
        private readonly ApplicationParameters $application,
    ) {
        parent::__construct($cache, $manager);
    }

    /**
     * Gets the application parameters.
     */
    public function getApplication(): ApplicationParameters
    {
        return $this->application;
    }

    /**
     * Gets the customer information.
     */
    public function getCustomerInformation(): CustomerInformation
    {
        $printAddress = $this->getOptions()->isPrintAddress();

        return $this->application->getCustomerInformation($printAddress);
    }

    #[\Override]
    public function getDefaultValues(): array
    {
        return $this->getParametersDefaultValues($this->getDefaultParameters());
    }

    /**
     * Gets the display parameter.
     */
    #[\Override]
    public function getDisplay(): DisplayParameter
    {
        return $this->display ??= $this->getCachedParameter(
            DisplayParameter::class,
            $this->application->getDisplay()
        );
    }

    /**
     * Gets the home page parameter.
     */
    #[\Override]
    public function getHomePage(): HomePageParameter
    {
        return $this->homePage ??= $this->getCachedParameter(
            HomePageParameter::class,
            $this->application->getHomePage()
        );
    }

    /**
     * Gets the message parameter.
     */
    #[\Override]
    public function getMessage(): MessageParameter
    {
        return $this->message ??= $this->getCachedParameter(
            MessageParameter::class,
            $this->application->getMessage()
        );
    }

    /**
     * Gets the option parameter.
     */
    #[\Override]
    public function getOptions(): OptionsParameter
    {
        return $this->options ??= $this->getCachedParameter(
            OptionsParameter::class,
            $this->application->getOptions()
        );
    }

    #[\Override]
    protected function createProperty(string $name): UserProperty
    {
        return UserProperty::instance($name, $this->getUser());
    }

    #[\Override]
    protected function getDefaultParameters(): array
    {
        return [
            DisplayParameter::class => $this->application->getDisplay(),
            HomePageParameter::class => $this->application->getHomePage(),
            MessageParameter::class => $this->application->getMessage(),
            OptionsParameter::class => $this->application->getOptions(),
        ];
    }

    #[\Override]
    protected function loadProperties(): array
    {
        $user = $this->getUser();
        $repository = $this->manager->getRepository(UserProperty::class);

        return $this->mapToKeyValue(
            $repository->findByUser($user),
            static fn (UserProperty $property): array => [$property->getName() => $property]
        );
    }

    private function getUser(): User
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \DomainException('User not found.');
        }

        return $user;
    }
}
