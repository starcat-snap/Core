<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Event;

use Shopware\Core\Framework\Event\EventData\MailRecipientStruct;
use Shopware\Core\Framework\Log\Package;

#[Package('business-ops')]
#[IsFlowEventAware]
interface MailAware
{
    public const MAIL_STRUCT = 'mailStruct';

    public const SALES_CHANNEL_ID = 'salesChannelId';

    public function getMailStruct(): MailRecipientStruct;

    public function getSalesChannelId(): ?string;
}
