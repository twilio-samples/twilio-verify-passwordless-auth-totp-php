<?php

declare(strict_types=1);

namespace App\Config\RequiredEnvironmentVariables\Spec;

interface SpecInterface
{
    /**
     * @property array<int,string> The list of environment variables which this spec requires
     */
    public array $envVars { get; }
}
