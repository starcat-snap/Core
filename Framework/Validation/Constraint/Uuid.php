<?php declare(strict_types=1);

namespace SnapAdmin\Core\Framework\Validation\Constraint;

use SnapAdmin\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraint;

#[Package('core')]
class Uuid extends Constraint
{
    final public const INVALID_MESSAGE = 'The string "{{ string }}" is not a valid uuid.';
    final public const INVALID_TYPE_MESSAGE = 'This value should be of type string.';
}
