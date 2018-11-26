<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Api\Controller;

use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class AccessKeyController extends AbstractController
{
    /**
     * @Route("/api/_action/v{version}/access-key/intergration", name="api.action.access-key.integration", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function generateIntegrationKey(): JsonResponse
    {
        return new JsonResponse([
            'accessKey' => AccessKeyHelper::generateAccessKey('integration'),
            'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
        ]);
    }

    /**
     * @Route("/api/_action/v{version}/access-key/user", name="api.action.access-key.user", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function generateUserKey(): JsonResponse
    {
        return new JsonResponse([
            'accessKey' => AccessKeyHelper::generateAccessKey('user'),
            'secretAccessKey' => AccessKeyHelper::generateSecretAccessKey(),
        ]);
    }

    /**
     * @Route("/api/_action/v{version}/access-key/sales-channel", name="api.action.access-key.sales-channel", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function generateSalesChannelKey(): JsonResponse
    {
        return new JsonResponse([
            'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
        ]);
    }
}
