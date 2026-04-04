<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Password_resets
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "string", length: 255)]
    private string $email;

    #[ORM\Column(type: "string", length: 10)]
    private string $code;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $expires_at;

    #[ORM\Column(type: "boolean")]
    private bool $used;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $created_at;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($value)
    {
        $this->email = $value;
    }

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($value)
    {
        $this->code = $value;
    }

    public function getExpires_at()
    {
        return $this->expires_at;
    }

    public function setExpires_at($value)
    {
        $this->expires_at = $value;
    }

    public function getUsed()
    {
        return $this->used;
    }

    public function setUsed($value)
    {
        $this->used = $value;
    }

    public function getCreated_at()
    {
        return $this->created_at;
    }

    public function setCreated_at($value)
    {
        $this->created_at = $value;
    }
}
