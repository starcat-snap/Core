<?php declare(strict_types=1);

namespace Shopware\Core\Content\Flow\Dispatching\Action;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Content\Flow\Dispatching\DelayableAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Event\CustomerAware;
use Shopware\Core\Framework\Event\FlowEvent;
use Shopware\Core\Framework\Feature;

/**
 * @package business-ops
 *
 * @deprecated tag:v6.5.0 - reason:remove-subscriber - FlowActions won't be executed over the event system anymore,
 * therefore the actions won't implement the EventSubscriberInterface anymore.
 */
class SetCustomerCustomFieldAction extends FlowAction implements DelayableAction
{
    use CustomFieldActionTrait;

    private Connection $connection;

    private EntityRepository $customerRepository;

    /**
     * @internal
     */
    public function __construct(
        Connection $connection,
        EntityRepository $customerRepository
    ) {
        $this->connection = $connection;
        $this->customerRepository = $customerRepository;
    }

    public static function getName(): string
    {
        return 'action.set.customer.custom.field';
    }

    /**
     * @deprecated tag:v6.5.0 - reason:remove-subscriber - Will be removed
     */
    public static function getSubscribedEvents(): array
    {
        if (Feature::isActive('v6.5.0.0')) {
            return [];
        }

        return [
            self::getName() => 'handle',
        ];
    }

    /**
     * @return array<int, string>
     */
    public function requirements(): array
    {
        return [CustomerAware::class];
    }

    /**
     * @deprecated tag:v6.5.0 Will be removed, implement handleFlow instead
     */
    public function handle(FlowEvent $event): void
    {
        Feature::triggerDeprecationOrThrow(
            'v6.5.0.0',
            Feature::deprecatedMethodMessage(__CLASS__, __METHOD__, 'v6.5.0.0')
        );

        $baseEvent = $event->getEvent();
        if (!$baseEvent instanceof CustomerAware) {
            return;
        }

        $this->update($baseEvent->getContext(), $event->getConfig(), $baseEvent->getCustomerId());
    }

    public function handleFlow(StorableFlow $flow): void
    {
        if (!$flow->hasStore(CustomerAware::CUSTOMER_ID)) {
            return;
        }

        $this->update($flow->getContext(), $flow->getConfig(), $flow->getStore(CustomerAware::CUSTOMER_ID));
    }

    /**
     * @param array<string, mixed> $config
     */
    private function update(Context $context, array $config, string $customerId): void
    {
        /** @var CustomerEntity $customer */
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $context)->first();

        $customFields = $this->getCustomFieldForUpdating($customer->getCustomfields(), $config);

        if ($customFields === null) {
            return;
        }

        $customFields = empty($customFields) ? null : $customFields;

        $this->customerRepository->update([
            [
                'id' => $customerId,
                'customFields' => $customFields,
            ],
        ], $context);
    }
}
