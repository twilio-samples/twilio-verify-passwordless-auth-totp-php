<?php

declare(strict_types=1);

namespace App;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App as SlimApp;
use Slim\Interfaces\RouteInterface;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Views\Twig;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class Application
{
    public function __construct(private readonly SlimApp $app)
    {
        $app->add(new ContentLengthMiddleware());
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(true, true, true);
    }

    /**
     * setupRoutes sets up the application's routing table
     */
    public function setupRoutes(): void
    {
        $this->app->get('/', [$this, 'displayCreateTotpFactorForm']);
        $this->app->post('/', [$this, 'processCreateTotpFactorForm']);
        $this->app->get('/challenge', [$this, 'displayVerifyUserForm']);
        $this->app->post('/challenge', [$this, 'processVerifyUserForm']);
        $this->app->get('/token', [$this, 'showQRCodeForm']);
        $this->app->post('/token', [$this, 'processQRCodeForm']);
    }

    /**
     * getRoutes returns the application's current routes
     *
     * @return RouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->app->getRouteCollector()->getRoutes();
    }

    /**
     * run launches the application
     */
    public function run(): void
    {
        $this->app->run();
    }

    /**
     * This renders the form where the user can enter their username to set
     * up TOTP-based 2FA.
     */
    public function displayCreateTotpFactorForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'enter-username.html.twig', []);
    }

    /**
     * This processes the handleShowCreateNewFactorForm and creates a new
     * TOTP factor with Twilio. On success, it redirects the user to
     * handleCreateQRCode
     */
    public function processCreateTotpFactorForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $response;
    }

    /**
     * This renders the form with the QR code that the user needs to scan
     * to verify themselves.
     *
     * After scanning the QR code and retrieving the TOTP code, they can
     * enter it into the form and click "Verify" to finish the verification
     * process.
     */
    public function displayVerifyUserForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'verify-user.html.twig', []);
    }

    /**
     * This processes the verify user form. It redirects the user to the
     * showQRCodeForm on success.
     */
    public function processVerifyUserForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $response;
    }

    /**
     * This renders a form with a QR code and a field for the the TOTP to
     * be entered.
     */
    public function showQRCodeForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $view = Twig::fromRequest($request);
        return $view->render($response, 'enter-code.html.twig', []);
    }

    /**
     * This processes the showQRCodeForm form and verifies the TOTP
     * submitted in the showQRCodeForm.
     */
    public function processQRCodeForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        return $response;
    }
}
