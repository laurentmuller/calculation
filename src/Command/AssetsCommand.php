<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Abstrac command for assets.
 *
 * @author Laurent Muller
 */
abstract class AssetsCommand extends Command
{
    use FileTrait;

    /**
     * Constructor.
     *
     * @param string $name the command name
     */
    public function __construct(string $name)
    {
        parent::__construct($name);
    }

    /**
     * Executes the command.
     *
     * @return int 0 if everything went fine, or an exit code
     */
    abstract protected function doExecute(InputInterface $input, OutputInterface $output): int;

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // to output messages
        $this->output = $output;

        // delegate to subclass
        return $this->doExecute($input, $output);
    }

    /**
     * Gets the project directory.
     *
     * @return string|null the project directory, if found; null otherwise
     */
    protected function getProjectDir(): ?string
    {
        /** @var \Symfony\Bundle\FrameworkBundle\Console\Application|null $application */
        $application = $this->getApplication();
        if (!$application) {
            $this->writeError('The Application is not defined.');

            return null;
        }

        /** @var \Symfony\Component\HttpKernel\KernelInterface|null $kernel */
        $kernel = $application->getKernel();
        if (!$kernel) {
            $this->writeError('The Kernel is not defined.');

            return null;
        }

        return $kernel->getProjectDir();
    }

    /**
     * Gets the public directory.
     *
     * @return string|null the public directory, if found; null otherwise
     */
    protected function getPublicDir(): ?string
    {
        if ($projectDir = $this->getProjectDir()) {
            return $projectDir . '/public';
        }

        return null;
    }
}
