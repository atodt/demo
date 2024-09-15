<?php

namespace App\Tests\Components;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpKernel\HttpKernelBrowser;

class ProductListApiControllerTest extends WebTestCase
{
    private HttpKernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
    }

    protected function tearDown(): void
    {
        restore_exception_handler();
        parent::tearDown();
    }

    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    public function testRateLimiter(): void
    {
        // Make 100 requests to the API endpoint
        for ($i = 0; $i < 200; $i++) {
            $this->client->request('GET', '/api/products');
            var_dump($i . ":" . $this->client->getResponse()->getStatusCode());

//          If the request count exceeds the rate limit, the response status code should be 429 (Too Many Requests)
            if ($i >= 100) {
                $this->assertSame(429, $this->client->getResponse()->getStatusCode());
            } else {
                $this->assertSame(200, $this->client->getResponse()->getStatusCode());
            }
        }
    }
}
