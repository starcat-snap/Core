<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Cart\Facade;

use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Framework\Script\Service\ArrayFacade;
use Shopware\Core\Framework\Uuid\Uuid;

class ItemFacade
{
    protected LineItem $item;

    protected CartFacadeHelper $services;

    /**
     * @internal
     */
    public function __construct(LineItem $item, CartFacadeHelper $services)
    {
        $this->item = $item;
        $this->services = $services;
    }

    public function getPrice(): ?PriceFacade
    {
        if ($this->item->getPrice()) {
            return new PriceFacade($this->item->getPrice(), $this->services);
        }

        return null;
    }

    public function take(int $quantity, ?string $key = null): ?ItemFacade
    {
        if (!$this->item->isStackable()) {
            return null;
        }

        if ($quantity >= $this->item->getQuantity()) {
            return null;
        }

        $new = clone $this->item;
        $new->setId($key ?? Uuid::randomHex());
        $new->setQuantity($quantity);

        $this->item->setQuantity(
            $this->item->getQuantity() - $quantity
        );

        return new ItemFacade($new, $this->services);
    }

    public function getId(): string
    {
        return $this->item->getId();
    }

    public function getReferencedId(): ?string
    {
        return $this->item->getReferencedId();
    }

    public function getQuantity(): int
    {
        return $this->item->getQuantity();
    }

    public function getLabel(): ?string
    {
        return $this->item->getLabel();
    }

    public function getPayload(): ArrayFacade
    {
        return new ArrayFacade(
            $this->item->getPayloadByReference(),
            function (array $payload): void {
                $this->item->setPayload($payload);
            }
        );
    }

    public function getChildren(): ItemsFacade
    {
        return new ItemsFacade($this->item->getChildren(), $this->services);
    }

    public function getType(): string
    {
        return $this->item->getType();
    }

    /**
     * @internal
     */
    public function getItem(): LineItem
    {
        return $this->item;
    }
}
