<?php

namespace NetBull\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: 'settings')]
#[ORM\Entity]
class Setting
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $name = null;

    /**
     * @var string|null
     */
    #[ORM\Column(nullable: true)]
    private ?string $value = null;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'grp', nullable: true)]
    private ?string $grouping = null;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return Setting
     */
    public function setName(?string $name): Setting
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->value;
    }

    /**
     * @param string|null $value
     * @return Setting
     */
    public function setValue(?string $value): Setting
    {
        $this->value = $value;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getGrouping(): ?string
    {
        return $this->grouping;
    }

    /**
     * @param string|null $grouping
     * @return Setting
     */
    public function setGrouping(?string $grouping): Setting
    {
        $this->grouping = $grouping;
        return $this;
    }
}
