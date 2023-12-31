<?php declare(strict_types=1);

namespace SnapAdmin\Core\System\CustomEntity\Xml;

use SnapAdmin\Core\Framework\Log\Package;
use SnapAdmin\Core\Framework\Struct\XmlElement;

/**
 * @internal only for use by the app-system, will be considered internal from v6.4.0 onward
 */
#[Package('core')]
class Entities extends XmlElement
{
    /**
     * @var list<Entity>
     */
    protected array $entities = [];

    /**
     * @return list<Entity>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }

    protected static function parse(\DOMElement $element): array
    {
        $entities = [];
        foreach ($element->getElementsByTagName('entity') as $entity) {
            $entities[] = Entity::fromXml($entity);
        }

        return ['entities' => $entities];
    }
}
