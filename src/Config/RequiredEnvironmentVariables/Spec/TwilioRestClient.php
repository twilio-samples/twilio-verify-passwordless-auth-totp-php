<?php

declare(strict_types=1);

namespace App\Config\RequiredEnvironmentVariables\Spec;

/**
 * Defines the required environment variables for the Twilio Rest Client
 *
 * @see https://github.com/twilio/twilio-php/blob/main/src/Twilio/Base/BaseClient.php#L20-L21
 */
class TwilioRestClient implements SpecInterface
{
    public array $envVars = [
        'TWILIO_ACCOUNT_SID',
        'TWILIO_AUTH_TOKEN',
    ];
}
