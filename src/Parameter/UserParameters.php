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

use App\Entity\User;
use App\Entity\UserProperty;
use App\Repository\UserPropertyRepository;
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
    public function __construct(
        #[Target('calculation.user')]
        CacheInterface $cache,
        EntityManagerInterface $manager,
        private readonly Security $security,
        private readonly ApplicationParameters $application,
    ) {
        parent::__construct($cache, $manager);
    }

    #[\Override]
    public function getDefaultValues(): array
    {
        return $this->getParametersDefaultValues(
            $this->application->getDisplay(),
            $this->application->getHomePage(),
            $this->application->getMessage(),
            $this->application->getOptions(),
        );
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
    public function save(): bool
    {
        return $this->saveParameters([
            $this->display,
            $this->homePage,
            $this->message,
            $this->options,
        ], [
            $this->application->getDisplay(),
            $this->application->getHomePage(),
            $this->application->getMessage(),
            $this->application->getOptions(),
        ]);
    }

    #[\Override]
    protected function createProperty(string $name): UserProperty
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User not found.');
        }

        return UserProperty::instance($name, $user);
    }

    #[\Override]
    protected function getRepository(): UserPropertyRepository
    {
        return $this->manager->getRepository(UserProperty::class);
    }

    #[\Override]
    protected function loadProperties(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return [];
        }

        return $this->getRepository()
            ->findByUser($user);
    }
}
