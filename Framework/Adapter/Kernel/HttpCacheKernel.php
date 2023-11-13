<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Adapter\Kernel;

use Psr\EventDispatcher\EventDispatcherInterface;
use Shopware\Core\Framework\Event\BeforeSendResponseEvent;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\HttpCache;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\HttpCache\SurrogateInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;

#[Package('core')]
class HttpCacheKernel extends HttpCache
{
    final public const MAINTENANCE_WHITELIST_HEADER = 'sw-maintenance-whitelist';

    private StoreInterface $store;

    /**
     * @param array<mixed> $options
     */
    public function __construct(
        HttpKernelInterface $kernel,
        StoreInterface $store,
        SurrogateInterface $surrogate,
        array $options,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly bool $externalReverseProxyEnabled
    ) {
        $this->store = $store;
        parent::__construct($kernel, $store, $surrogate, $options);
    }

    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true): Response
    {
        /**
         * When we have an external reverse proxy which is ESI capable, we can't use the internal HttpCache, as it will resolve the ESI tags
         */
        if ($this->externalReverseProxyEnabled) {
            $response = $this->getKernel()->handle($request, $type, $catch);

            // Call store trigger to write the cache tags
            // @todo: fix this shit.
            $this->store->write($request, $response);
        } else {
            $response = parent::handle($request, $type, $catch);

            if ($ips = $response->headers->get(self::MAINTENANCE_WHITELIST_HEADER)) {
                $ips = array_filter(explode(',', $ips));

                if (IpUtils::checkIp((string) $request->getClientIp(), $ips)) {
                    $response = $this->getKernel()->handle($request, $type, $catch);
                }
            }

            $response->headers->remove(self::MAINTENANCE_WHITELIST_HEADER);
        }

        $event = new BeforeSendResponseEvent($request, $response);
        $this->eventDispatcher->dispatch($event);

        return $event->getResponse();
    }
}
