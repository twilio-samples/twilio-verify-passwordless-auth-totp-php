<?php

declare(strict_types=1);

namespace App;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use SlimSession\Helper as SlimSessionHelper;
use Slim\App as SlimApp;
use Slim\Flash\Messages;
use Slim\Interfaces\RouteInterface;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Views\Twig;
use Twilio\Rest\Client;
use chillerlan\QRCode\QRCode;

use function assert;
use function bin2hex;
use function random_bytes;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class Application
{
    private Client $twilio;
    private SlimSessionHelper $session;
    private string $verifyServiceSid;

    public function __construct(private readonly SlimApp $app)
    {
        $app->add(new ContentLengthMiddleware());
        $app->addBodyParsingMiddleware();
        $app->addRoutingMiddleware();
        $app->addErrorMiddleware(true, true, true);

        $this->session          = new SlimSessionHelper();
        $this->verifyServiceSid = $_ENV['TWILIO_VERIFY_SERVICE_SID'];

        $twilio = $this->app->getContainer()->get(Client::class);
        assert($twilio instanceof Client);
        $this->twilio = $twilio;
    }

    /**
     * setupRoutes sets up the application's routing table
     */
    public function setupRoutes(): void
    {
        $this->app->get('/', [$this, 'displayCreateTotpFactorForm'])->setName("create-totp.display");
        $this->app->post('/', [$this, 'processCreateTotpFactorForm'])->setName("create-totp.process");

        $this->app->get('/challenge', [$this, 'displayVerifyUserForm'])->setName("verify-user.display");
        $this->app->post('/challenge', [$this, 'processVerifyUserForm'])->setName("verify-user.process");

        $this->app->get('/token', [$this, 'showQRCodeForm'])->setName("qrcode.display");
        $this->app->post('/token', [$this, 'processQRCodeForm'])->setName("qrcode.process");
    }

    /**
     * getRoutes returns the application's current routes
     *
     * @return RouteInterface[]
     */
    private function getRoutes(): array
    {
        return $this->app->getRouteCollector()->getRoutes();
    }

    private function getNamedRoute(string $routeName): RouteInterface
    {
        return $this->app->getRouteCollector()->getNamedRoute($routeName);
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
        $postData = $request->getParsedBody();
        $username = $postData['username'] ?? '';

        $identity = substr(bin2hex(random_bytes(55)), 0, 55);
        $this->session->set('identity', $identity);

        $factor = $this->twilio
            ->verify
            ->v2
            ->services($this->verifyServiceSid)
            // This needs to be created with each new session
            // See: https://www.twilio.com/docs/verify/api/factor#create-a-new-factor-resource
            ->entities($identity)
            ->newFactors
            ->create($username, "totp");

        $this->session->set('friendly_name', $factor->friendlyName);
        $this->session->set('sid', $factor->sid);
        $this->session->set('otp_uri', $factor->binding['uri']);
        $this->session->set('url', $factor->url);

        $response = $response
            ->withHeader(
                'Location',
                $factor->status === 'unverified'
                    ? $this->getNamedRoute("verify-user.display")->getPattern()
                    : $this->getNamedRoute("create-totp.display")->getPattern(),
            )
            ->withStatus(StatusCodeInterface::STATUS_FOUND);

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

        $qrCodeUri = $this->session->get('otp_uri') ?? '';
        $qrcode    = (new QRCode())->render($qrCodeUri);

        return $view->render(
            $response,
            'verify-user.html.twig',
            [
                'qr_code' => $qrcode,
            ],
        );
    }

    /**
     * This processes the verify user form. It redirects the user to the
     * showQRCodeForm on success.
     */
    public function processVerifyUserForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $postData = $request->getParsedBody();
        $code     = $postData['code'] ?? '';
        $entity   = $this->session->get('identity') ?? '';
        $factors  = $this->session->get('sid') ?? '';

        $factor = $this->twilio
            ->verify
            ->v2
            ->services($this->verifyServiceSid)
            ->entities($entity)
            ->factors($factors)
            ->update(
                [
                    "authPayload" => $code,
                ],
            );

        if ($factor->status === 'verified') {
            $flash = $this->app->getContainer()->get(Messages::class);
            assert($flash instanceof Messages);
            $flash->addMessage('message', "Factor setup complete!");
        }

        $response = $response
            ->withHeader(
                'Location',
                $factor->status === 'verified'
                    ? $this->getNamedRoute("qrcode.display")->getPattern()
                    : $this->getNamedRoute("verify-user.display")->getPattern(),
            )
            ->withStatus(StatusCodeInterface::STATUS_FOUND);

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
        $view     = Twig::fromRequest($request);
        $username = $this->session->get('friendly_name') ?? '';
        $identity = $this->session->get('identity') ?? '';

        $flash = $this->app->getContainer()->get(Messages::class);
        assert($flash instanceof Messages);
        $message = $flash->getFirstMessage('message');

        return $view->render(
            $response,
            'enter-code.html.twig',
            [
                'identity' => $identity,
                'message'  => $message,
                'username' => $username,
            ],
        );
    }

    /**
     * This processes the showQRCodeForm form and verifies the TOTP
     * submitted in the showQRCodeForm.
     */
    public function processQRCodeForm(
        ServerRequestInterface $request,
        ResponseInterface $response,
    ): ResponseInterface {
        $postData = $request->getParsedBody();
        $code     = $postData['code'] ?? '';
        $entity   = $this->session->get('identity') ?? '';
        $factors  = $this->session->get('sid') ?? '';

        $challenge = $this->twilio
            ->verify
            ->v2
            ->services($this->verifyServiceSid)
            ->entities($entity)
            ->challenges->create(
                $factors,
                [
                    "authPayload" => $code,
                ],
            );

        $flash = $this->app->getContainer()->get(Messages::class);
        assert($flash instanceof Messages);
        $flash->addMessage('message', $challenge->status === "approved" ? "Verification success." : "Verification failed.");

        // Add a flash message stating whether the code is valid or not
        $response = $response
            ->withHeader('Location', "/token")
            ->withStatus(StatusCodeInterface::STATUS_FOUND);

        return $response;
    }
}
