<?php
declare(strict_types=1);
namespace routes;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\UriFactory;



class UserRoutesTest extends TestCase
{
    protected $app;

    public function setUp(): void
    {
        // Include necessary files and bootstrap your Slim app
        require __DIR__ . '/../../vendor/autoload.php';
        require __DIR__ . '/../../src/db/db.php';
        $this->app = AppFactory::create();
        $userRoutes = require __DIR__ . '/../../src/routes/users.php';
        $userRoutes($this->app);
    }

    public function testGetUsersRoute(): void
    {
        $uriFactory = new UriFactory();
        $uri = $uriFactory->createUri('http://localhost/chat_app/public/users');
        $request = $this->createMock(Request::class);
        $response = $this->app->handle($request->withMethod('GET')->withUri($uri));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $body = json_decode((string)$response->getBody(), true);
        $this->assertIsArray($body);
        // Add more specific assertions based on your expected response structure
    }
    
}

