<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * PasswordResets
 *
 * @ORM\Table(name="password_resets", indexes={@ORM\Index(name="idx_code", columns={"code"}), @ORM\Index(name="idx_expires", columns={"expires_at"}), @ORM\Index(name="idx_email", columns={"email"})})
 * @ORM\Entity
 */
class PasswordResets
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="code", type="string", length=10, nullable=false)
     */
    private $code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expires_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $expiresAt = 'CURRENT_TIMESTAMP';

    /**
     * @var bool|null
     *
     * @ORM\Column(name="used", type="boolean", nullable=true)
     */
    private $used = '0';

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    private $createdAt = 'CURRENT_TIMESTAMP';

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getExpiresAt(): ?\DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeInterface $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(?bool $used): static
    {
        $this->used = $used;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }


}
