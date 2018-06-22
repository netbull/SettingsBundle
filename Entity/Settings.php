<?php

namespace NetBull\SettingsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="settings")
 * @ORM\Entity
 */
class Setting
{
    const GROUP_GENERAL = 'general';
    const GROUP_STORE = 'store';

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $value;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $group = self::GROUP_GENERAL;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     *
     * @return Setting
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $value
     *
     * @return Setting
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return null|string
     */
    public function getGroup(): ?string
    {
        return $this->group;
    }

    /**
     * @param null|string $group
     *
     * @return Setting
     */
    public function setGroup(?string $group): Setting
    {
        $this->group = $group;

        return $this;
    }
}
