<?php

declare(strict_types=1);

namespace AppTest;

use App\Config\RequiredEnvironmentVariables;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;

use function count;

/**
 * This class tests the RequiredEnvironmentVariables class to ensure that it
 * only returns a list of the required environment variables.
 */
final class RequiredEnvironmentVariablesTest extends TestCase
{
    /**
     * @param array<int,EnvironmentVariableInterface> $requiredEnvironmentVariable
     */
    #[TestWith(
        [
            [
                RequiredEnvironmentVariables\Spec\TwilioRestClient::class,
            ],
            [
                'TWILIO_ACCOUNT_SID',
                'TWILIO_AUTH_TOKEN',
            ],
        ],
        'Load only the environment variables required for a Twilio Rest Client',
    )]
    #[TestWith(
        [
            [
                RequiredEnvironmentVariables\Spec\TwilioRestClient::class,
                RequiredEnvironmentVariables\Spec\TwilioPhoneNumber::class,
            ],
            [
                'TWILIO_ACCOUNT_SID',
                'TWILIO_AUTH_TOKEN',
                'TWILIO_PHONE_NUMBER',
            ],
        ],
        'Load only the environment variables required for a Twilio Rest Client, and a Twilio phone number',
    )]
    public function testOnlyRequiresTheRequiredEnvironmentVariables(
        array $requiredEnvironmentVariables,
        array $expectedEnvironmentVariables,
    ): void {
        $requiredEnvVars = new RequiredEnvironmentVariables($requiredEnvironmentVariables);
        $this->assertCount(count($expectedEnvironmentVariables), $requiredEnvVars->getEnvVars());
        $this->assertEquals($expectedEnvironmentVariables, $requiredEnvVars->getEnvVars());
    }
}
