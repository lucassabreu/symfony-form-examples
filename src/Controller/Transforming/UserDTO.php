<?php

namespace App\Controller\Transforming;

class UserDTO
{
    private string $username;
    private string $name;
    private Phone $phone;
    private Money $cost;

    public function setCost(Money $cost): self
    {
        $this->cost = $cost;

        return $this;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setPhone(Phone $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function phone(): Phone
    {
        return $this->phone;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function cost(): Money
    {
        return $this->cost;
    }
}
