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
use App\Attribute\GetPost;
use App\Interfaces\RoleInterface;
use App\Report\CommandsReport;
use App\Service\CommandDataService;
use App\Service\CommandFormService;
use App\Service\CommandService;
use App\Traits\CacheKeyTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for the console commands.
 *
 * @psalm-import-type CommandType from CommandService
 */
#[AsController]
#[Route(path: '/command', name: 'command_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class CommandController extends AbstractController
{
    use CacheKeyTrait;

    private const LAST_COMMAND = 'last_command';

    /**
     * Render a single command.
     */
    #[Get(path: '/content', name: 'content')]
    public function command(
        #[MapQueryParameter]
        string $name,
        Request $request,
        CommandService $service
    ): JsonResponse {
        if (!$service->hasCommand($name)) {
            return $this->jsonFalse([
                'message' => $this->trans('command.list.error', ['%name%' => $name]),
            ]);
        }

        $command = $service->getCommand($name);
        $request->getSession()->set(self::LAST_COMMAND, $name);
        $view = $this->renderView('command/_command.htm.twig', ['command' => $command]);

        return $this->jsonTrue(['content' => $view]);
    }

    /**
     * Show all commands.
     */
    #[Get(path: '', name: 'all')]
    public function commands(
        Request $request,
        CommandService $service,
        #[MapQueryParameter]
        ?string $name = null
    ): Response {
        $count = $service->count();
        if (0 === $count) {
            return $this->redirectToHomePage('command.list.empty');
        }

        /** @psalm-var ?string $commandName */
        $commandName = $name ?? $request->getSession()->get(self::LAST_COMMAND);
        if (\is_string($commandName) && $service->hasCommand($commandName)) {
            $command = $service->getCommand($commandName);
        } else {
            $command = $service->first();
        }
        $root = $this->trans('command.list.available');
        $parameters = [
            'count' => $count,
            'command' => $command,
            'groups' => $service->getGroupedNames($root),
        ];

        return $this->render('command/commands.html.twig', $parameters);
    }

    /**
     * Execute a command.
     */
    #[GetPost(path: '/execute', name: 'execute')]
    public function execute(
        #[MapQueryParameter]
        string $name,
        Request $request,
        CommandService $service,
        CommandFormService $formService,
        CommandDataService $dataService,
    ): Response {
        if (!$service->hasCommand($name)) {
            throw $this->createTranslatedNotFoundException('command.list.error', ['%name%' => $name]);
        }

        /** @psalm-var CommandType $command */
        $command = $service->getCommand($name); // @phpstan-ignore varTag.nativeType
        $session = $request->getSession();
        $key = $this->cleanKey('command.execute.' . $name);
        $data = $this->getCommandData($session, $dataService, $key, $command);

        // form
        $form = $formService->createForm($command, $data);
        if ($this->handleRequestForm($request, $form)) {
            try {
                /** @psalm-var array<string, array|scalar|null> $data */
                $data = $form->getData();
                $session->set($key, $data);
                $parameters = $dataService->createParameters($command, $data);
                $result = $service->execute($name, $parameters);

                return $this->render('command/command_execute_result.html.twig', [
                    'parameters' => $parameters,
                    'command' => $command,
                    'result' => $result,
                ]);
            } catch (\Exception $e) {
                return $this->renderFormException('command.result.error', $e);
            }
        }

        $view = $form->createView();
        $parameters = [
            'arguments' => [
                'texts' => $formService->filter($view, CommandFormService::ARGUMENT_TEXT),
                'checkboxes' => $formService->filter($view, CommandFormService::ARGUMENT_BOOL),
            ],
            'options' => [
                'texts' => $formService->filter($view, CommandFormService::OPTION_TEXT),
                'checkboxes' => $formService->filter($view, CommandFormService::OPTION_BOOL),
            ],
            'command' => $command,
            'form' => $view,
        ];

        return $this->render('command/command_execute_query.html.twig', $parameters);
    }

    /**
     * Export commands to a PDF document.
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(CommandService $service): Response
    {
        if (0 === $service->count()) {
            return $this->redirectToHomePage('command.list.empty');
        }

        $root = $this->trans('command.list.available');
        $groups = $service->getGroupedCommands($root);
        $report = new CommandsReport($this, $groups);

        return $this->renderPdfDocument($report);
    }

    /**
     * @psalm-param CommandType $command
     *
     * @psalm-return array<string, array|scalar|null>
     *
     * @phpstan-param array $command
     */
    private function getCommandData(
        SessionInterface $session,
        CommandDataService $dataService,
        string $key,
        array $command
    ): array {
        $data = $dataService->createData($command);
        /** @psalm-var array<string, array|scalar|null> $existing */
        $existing = (array) $session->get($key, []);
        if ([] === $existing) {
            return $data;
        }

        return $dataService->validateData($command, \array_merge($data, $existing));
    }
}
