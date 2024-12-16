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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Contains user parameters.
 *
 * @extends AbstractParameterContainer<UserProperty>
 */
class UserParameters extends AbstractParameterContainer
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

    public function getDisplay(): DisplayParameter
    {
        return $this->display ??= $this->getCachedParameter(
            DisplayParameter::class,
            $this->application->getDisplay()
        );
    }

    public function getHomePage(): HomePageParameter
    {
        return $this->homePage ??= $this->getCachedParameter(
            HomePageParameter::class,
            $this->application->getHomePage()
        );
    }

    public function getMessage(): MessageParameter
    {
        return $this->message ??= $this->getCachedParameter(
            MessageParameter::class,
            $this->application->getMessage()
        );
    }

    public function getOption(): OptionParameter
    {
        return $this->option ??= $this->getCachedParameter(
            OptionParameter::class,
            $this->application->getOption()
        );
    }

    /**
     * Save parameters.
     *
     * @return bool true if one of the parameters has changed
     */
    public function save(): bool
    {
        $saved = false;
        if ($this->saveParameter($this->display, $this->application->getDisplay())) {
            $saved = true;
        }
        if ($this->saveParameter($this->homePage, $this->application->getHomePage())) {
            $saved = true;
        }
        if ($this->saveParameter($this->message, $this->application->getMessage())) {
            $saved = true;
        }
        if ($this->saveParameter($this->option, $this->application->getOption())) {
            $saved = true;
        }

        return $saved;
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

        return $this->manager->getRepository(UserProperty::class)
            ->findOneByUserAndName($user, $name);
    }

    protected function getPropertyClass(): string
    {
        return UserProperty::class;
    }
}
