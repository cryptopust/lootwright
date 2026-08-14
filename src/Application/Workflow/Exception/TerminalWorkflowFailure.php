<?php

namespace Lootwright\Application\Workflow\Exception;

final class TerminalWorkflowFailure extends WorkflowException
{
    public function __construct(public readonly string $failureCode, string $message)
    {
        parent::__construct($message);
    }
}
