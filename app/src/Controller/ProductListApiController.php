<?php

declare(strict_types=1);

namespace App\Controller;

use App\Components\TokenBucketLimiter;
use App\Repository\ProductRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\Cache;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/products', name: 'api_product_list', methods: ['GET'])]
class ProductListApiController extends AbstractController
{
    /**
     * @param LoggerInterface $logger
     * @param ProductRepository $repository
     * @param TokenBucketLimiter $limiter
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ProductRepository $repository,
        private readonly TokenBucketLimiter $limiter,
    ) {
    }

    /**
     * Handles the request to fetch the list of products.
     *
     * @param Request $request the HTTP request object
     *
     * @return JsonResponse a JSON response containing the list of products or an error message
     */
    #[Cache(maxage: 0, public: false, mustRevalidate: true)]
    public function __invoke(Request $request): JsonResponse
    {
        if (!$this->limiter->allowRequest()) {
            $this->logger->info('Rate limit exceeded');
            return $this->json(['error' => 'Rate limit exceeded'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        $this->logger->info('Fetching products');
        try {
            $products = $this->repository->findAll();

            return $this->json(
                [
                    '_type' => 'ProductCollection',
                    'items' => $products,
                    '_links' => [
                        [
                            'href' => $request->getScheme().'://'.$request->getHost().$this->generateUrl('api_product_list'),
                            'method' => 'GET',
                            'rel' => 'self',
                            'title' => 'Products',
                        ],
                    ],
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            $this->logger->error('Error fetching products: '.$e->getMessage());

            return $this->json(['error' => 'Failed to get products '.$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
