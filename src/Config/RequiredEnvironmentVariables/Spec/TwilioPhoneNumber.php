<?php

declare(strict_types=1);

namespace App\Config\RequiredEnvironmentVariables\Spec;

/**
 * Defines the required environment variables for the Twilio Rest Client
 */
class TwilioPhoneNumber implements SpecInterface
{
    public array $envVars = [
        'TWILIO_PHONE_NUMBER',
    ];
}
