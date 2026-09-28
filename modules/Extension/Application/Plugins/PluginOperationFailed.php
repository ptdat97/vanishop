<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Modules\Extension\Domain\Plugin\DependencyProblem;
use RuntimeException;

final class PluginOperationFailed extends RuntimeException
{
    /**
     * @param  list<DependencyProblem>  $problems
     */
    public function __construct(string $message, public readonly array $problems = [])
    {
        $details = array_map(fn (DependencyProblem $p): string => "- [{$p->code}] {$p->message}", $problems);

        parent::__construct(trim($message.PHP_EOL.implode(PHP_EOL, $details)));
    }
}
