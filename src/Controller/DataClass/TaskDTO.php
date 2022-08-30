<?php

namespace App\Controller\DataClass;

class TaskDTO implements \JsonSerializable
{
    private int $id;
    private string $name;
    private ?\DateTime $dueDate = null;
    private string $assignee;
    /** @var string[] */
    private array $stackholders = [];

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setDueDate(?\DateTime $dueDate): void
    {
        $this->dueDate = $dueDate;
    }

    public function setAssignee(string $assignee): void
    {
        $this->assignee = $assignee;
    }

    /** @param string[] $stackholders */
    public function setStackholders(array $stackholders): void
    {
        $this->stackholders = $stackholders;
    }

    public function stackholders(): array
    {
        return $this->stackholders;
    }

    public function assignee(): string
    {
        return $this->assignee;
    }

    public function dueDate(): ?\DateTime
    {
        return $this->dueDate;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function id(): int
    {
        return $this->id;
    }

    /**
     * {@inheritDoc}
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'dueDate' => $this->dueDate?->format('Y-m-d'),
            'assignee' => $this->assignee,
            'stackholders' => $this->stackholders,
        ];
    }
}
