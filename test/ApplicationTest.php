<?php

declare(strict_types=1);

namespace AppTest;

use App\Application;
use PHPUnit\Framework\TestCase;
use Slim\App;

/**
 * This class encapsulates the central Slim application,
 * making it easier to create and test.
 */
final class ApplicationTest extends TestCase
{
    public function testCanInstantiateApplicationObject(): void
    {
        $this->assertInstanceOf(Application::class, new Application($this->createMock(App::class)));
    }

    public function testAppCanInitialiseRoutingTable(): void
    {
        $slimApp = $this->createMock(App::class);
        $slimApp
            ->expects($this->once())
            ->method("get");
        $app = new Application($slimApp);
        $app->setupRoutes();
    }
}
