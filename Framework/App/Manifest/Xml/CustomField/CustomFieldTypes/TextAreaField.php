<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App\Manifest\Xml\CustomField\CustomFieldTypes;

use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\CustomField\CustomFieldTypes;

/**
 * @internal only for use by the app-system
 */
#[Package('core')]
class TextAreaField extends CustomFieldType
{
    protected const TRANSLATABLE_FIELDS = ['label', 'help-text', 'placeholder'];

    /**
     * @var array
     */
    protected $placeholder = [];

    private function __construct(array $data)
    {
        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public static function fromXml(\DOMElement $element): CustomFieldType
    {
        return new self(self::parse($element, self::TRANSLATABLE_FIELDS));
    }

    public function getPlaceholder(): array
    {
        return $this->placeholder;
    }

    protected function toEntityArray(): array
    {
        return [
            'type' => CustomFieldTypes::HTML,
            'config' => [
                'placeholder' => $this->placeholder,
                'componentName' => 'sw-text-editor',
                'customFieldType' => 'textEditor',
            ],
        ];
    }
}
