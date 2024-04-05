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

namespace App\Controller;

use App\Attribute\Get;
use App\Interfaces\RoleInterface;
use App\Report\CommandReport;
use App\Response\PdfResponse;
use App\Service\CommandService;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the console commands.
 */
#[AsController]
#[Route(path: '/command')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class CommandController extends AbstractController
{
    private const LAST_COMMAND = 'last_command';

    /**
     * Render a single command.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '/content', name: 'command_content')]
    public function command(
        #[MapQueryParameter]
        string $name,
        Request $request,
        CommandService $service,
    ): JsonResponse {
        if (!$service->hasCommand($name)) {
            throw $this->createNotFoundException("Unable to find the command '$name'.");
        }

        $command = $service->getCommand($name);
        $request->getSession()->set(self::LAST_COMMAND, $name);
        $view = $this->renderView('admin/_command.htm.twig', ['command' => $command]);

        return $this->jsonTrue(['content' => $view]);
    }

    /**
     * Show all commands.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '', name: 'command_all')]
    public function commands(
        Request $request,
        CommandService $service,
        #[MapQueryParameter]
        ?string $name = null
    ): Response {
        $commands = $service->getCommands();
        /** @psalm-var ?string $commandName */
        $commandName = $name ?? $request->getSession()->get(self::LAST_COMMAND);
        if (\is_string($commandName) && $service->hasCommand($commandName)) {
            $command = $service->getCommand($commandName);
        } else {
            $command = \reset($commands);
        }

        $parameters = [
            'command' => $command,
            'names' => $service->getNames(),
        ];

        return $this->render('admin/commands.html.twig', $parameters);
    }

    /**
     * Export commands to a PDF document.
     *
     * @throws InvalidArgumentException
     */
    #[Get(path: '/pdf', name: 'command_pdf')]
    public function pdf(CommandService $service): PdfResponse
    {
        $commands = $service->getCommands();
        $report = new CommandReport($this, $commands);

        return $this->renderPdfDocument($report);
    }
}
