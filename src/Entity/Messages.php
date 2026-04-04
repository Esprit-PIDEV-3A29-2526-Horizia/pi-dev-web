<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
class Messages
{

    #[ORM\Id]
    #[ORM\Column(type: "integer")]
    private int $id;

    #[ORM\Column(type: "integer")]
    private int $user_id;

    #[ORM\Column(type: "text")]
    private string $content;

    #[ORM\Column(type: "boolean")]
    private bool $is_user;

    #[ORM\Column(type: "datetime")]
    private \DateTimeInterface $timestamp;

    public function getId()
    {
        return $this->id;
    }

    public function setId($value)
    {
        $this->id = $value;
    }

    public function getUser_id()
    {
        return $this->user_id;
    }

    public function setUser_id($value)
    {
        $this->user_id = $value;
    }

    public function getContent()
    {
        return $this->content;
    }

    public function setContent($value)
    {
        $this->content = $value;
    }

    public function getIs_user()
    {
        return $this->is_user;
    }

    public function setIs_user($value)
    {
        $this->is_user = $value;
    }

    public function getTimestamp()
    {
        return $this->timestamp;
    }

    public function setTimestamp($value)
    {
        $this->timestamp = $value;
    }
}
