<?php

declare(strict_types=1);

namespace App\Config;

use App\Config\RequiredEnvironmentVariables\Spec\SpecInterface;
use ArrayObject;

use function array_merge;

final class RequiredEnvironmentVariables
{
    private ArrayObject $environmentVariables;

    /**
     * @param array<int,SpecInterface> $envVarSpecs
     */
    public function __construct(array $envVarSpecs = [])
    {
        $temp = [];
        foreach ($envVarSpecs as $spec) {
            $temp = array_merge($temp, new $spec()->envVars);
        }
        $this->environmentVariables = new ArrayObject($temp);
    }

    /**
     * @return array<int,string> The list of required environment variables
     */
    public function getEnvVars(): array
    {
        return $this->environmentVariables->getArrayCopy();
    }
}
