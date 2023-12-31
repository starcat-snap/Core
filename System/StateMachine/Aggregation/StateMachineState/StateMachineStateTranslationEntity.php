<?php declare(strict_types=1);

namespace SnapAdmin\Core\System\StateMachine\Aggregation\StateMachineState;

use SnapAdmin\Core\Framework\DataAbstractionLayer\EntityCustomFieldsTrait;
use SnapAdmin\Core\Framework\DataAbstractionLayer\TranslationEntity;
use SnapAdmin\Core\Framework\Log\Package;

#[Package('system-settings')]
class StateMachineStateTranslationEntity extends TranslationEntity
{
    use EntityCustomFieldsTrait;

    /**
     * @var string|null
     */
    protected $name;

    /**
     * @var string
     */
    protected $stateMachineStateId;

    /**
     * @var StateMachineStateEntity|null
     */
    protected $stateMachineState;

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getStateMachineStateId(): string
    {
        return $this->stateMachineStateId;
    }

    public function setStateMachineStateId(string $stateMachineStateId): void
    {
        $this->stateMachineStateId = $stateMachineStateId;
    }

    public function getStateMachineState(): ?StateMachineStateEntity
    {
        return $this->stateMachineState;
    }

    public function setStateMachineState(StateMachineStateEntity $stateMachineState): void
    {
        $this->stateMachineState = $stateMachineState;
    }
}
