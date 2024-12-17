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
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains user parameters.
 *
 * @extends AbstractParameters<UserProperty>
 */
class UserParameters extends AbstractParameters
{
    #[Assert\Valid]
    private ?DisplayParameter $display = null;

    #[Assert\Valid]
    private ?HomePageParameter $homePage = null;

    #[Assert\Valid]
    private ?MessageParameter $message = null;

    #[Assert\Valid]
    private ?OptionParameter $option = null;

    public function __construct(
        #[Target('calculation.user')]
        CacheInterface $cache,
        EntityManagerInterface $manager,
        private readonly Security $security,
        private readonly ApplicationParameters $application,
    ) {
        parent::__construct($cache, $manager);
    }

    public function getDefaultValues(): array
    {
        return $this->getParametersDefaultValues(
            $this->application->getDisplay(),
            $this->application->getHomePage(),
            $this->application->getMessage(),
            $this->application->getOption(),
        );
    }

    /**
     * Gets the display parameter.
     */
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
    public function getOption(): OptionParameter
    {
        return $this->option ??= $this->getCachedParameter(
            OptionParameter::class,
            $this->application->getOption()
        );
    }

    public function save(): bool
    {
        return $this->saveParameters([
            $this->display,
            $this->homePage,
            $this->message,
            $this->option,
        ], [
            $this->application->getDisplay(),
            $this->application->getHomePage(),
            $this->application->getMessage(),
            $this->application->getOption(),
        ]);
    }

    protected function createProperty(string $name): UserProperty
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User not found.');
        }

        return UserProperty::instance($name, $user);
    }

    protected function findProperty(string $name): ?UserProperty
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            return null;
        }

        return $this->getRepository()
            ->findOneByUserAndName($user, $name);
    }

    protected function getRepository(): UserPropertyRepository
    {
        return $this->manager->getRepository(UserProperty::class);
    }
}
