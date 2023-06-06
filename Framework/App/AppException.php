<?php declare(strict_types=1);

namespace Shopware\Core\Framework\App;

use Shopware\Core\Framework\App\Exception\AppFlowException;
use Shopware\Core\Framework\App\Exception\InvalidAppFlowActionVariableException;
use Shopware\Core\Framework\HttpException;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\System\SystemConfig\Exception\XmlParsingException;
use Symfony\Component\HttpFoundation\Response;

#[Package('core')]
class AppException extends HttpException
{
    public const CANNOT_DELETE_COMPOSER_MANAGED = 'FRAMEWORK__APP_CANNOT_DELETE_COMPOSER_MANAGED';
    public const NOT_COMPATIBLE = 'FRAMEWORK__APP_NOT_COMPATIBLE';

    public static function cannotDeleteManaged(string $pluginName): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::CANNOT_DELETE_COMPOSER_MANAGED,
            'App {{ name }} is managed by Composer and cannot be deleted',
            ['name' => $pluginName]
        );
    }

    public static function notCompatible(string $pluginName): self
    {
        return new self(
            Response::HTTP_BAD_REQUEST,
            self::NOT_COMPATIBLE,
            'App {{ name }} is not compatible with this Shopware version',
            ['name' => $pluginName]
        );
    }

    public static function errorFlowCreateFromXmlFile(string $xmlFile, string $message): XmlParsingException
    {
        return new AppFlowException($xmlFile, $message);
    }

    public static function invalidAppFlowActionVariableException(
        string $appFlowActionId,
        string $param,
        string $message = '',
        int $code = 0
    ): InvalidAppFlowActionVariableException {
        return new InvalidAppFlowActionVariableException($appFlowActionId, $param, $message, $code);
    }
}
