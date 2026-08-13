# TOTP (Time-based One Time Passcode)-backed Passwordless Auth in PHP Using Twilio Verify

This project shows how to implement passwordless authentication in PHP with TOTP using Twilio Verify.

## Environment Variables

Copy `.env.example` to `.env`. Never commit `.env`.

```bash
cp .env.example .env
```

| Variable | Where to find | Format |
| -------- | ------------- | ------ |
| `TWILIO_ACCOUNT_SID` | Console homepage or Admin dropdown (top right) → Account Management → Keys & Credentials → API Keys & Tokens | Starts with `AC` |
| `TWILIO_AUTH_TOKEN` | Console homepage or Admin dropdown (top right) → Account Management → Keys & Credentials → API Keys & Tokens → click to reveal | 32-char string. Treat as a password. |
| `TWILIO_VERIFY_SERVICE_SID` | Console → Verify → Services | Starts with `VA` |
| `TWILIO_PHONE_NUMBER` | Console → Phone Numbers → Manage → Active Numbers | E.164 format: `+15551234567` |

## Commands

```bash
# Install
composer install

# Run
composer serve

# Test
composer test
```

## Project Structure

- `src/Application.php` — Slim app with all routes and request handlers
- `public/index.php` — entry point
- `templates/` — Twig templates for each step (enter username, verify user, enter TOTP code)
- `test/` — PHPUnit tests

## Agent Boundaries

**Always:**
- Confirm `.env` is configured before running any command
- Use the Environment Variables section to guide the user to each credential — don't ask them to find values without direction
- Confirm the app is running before asking the user to test it

**Never:**
- Run the app with missing or placeholder credentials
- Hardcode credentials or phone numbers in source files
- Skip the `cp .env.example .env` step

## Verify It's Working

1. Open http://localhost:8080 in a browser, enter a username, and submit the form.
2. Scan the QR code with an authenticator app (Google Authenticator, Authy, etc.), then enter the generated TOTP code — expect a "Factor setup complete!" confirmation, followed by a "Verification success." message on the next step.

## Twilio Resources

- [Twilio Console](https://console.twilio.com) — credentials, Verify service configuration
- [Twilio Verify TOTP docs](https://www.twilio.com/docs/verify/totp) — API reference for TOTP factors and challenges
- [Twilio PHP SDK](https://www.twilio.com/docs/libraries/php) — SDK reference and installation
