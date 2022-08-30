<?php

namespace App\Controller\Transforming;

class Phone
{
    private int $areaCode;
    private int $number;

    public function setAreaCode(int $areaCode): self
    {
        $this->areaCode = $areaCode;

        return $this;
    }

    public function setNumber(int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function areaCode(): int
    {
        return $this->areaCode;
    }

    public function number(): int
    {
        return $this->number;
    }
}
