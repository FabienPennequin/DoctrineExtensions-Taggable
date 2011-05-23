<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineExtensions\Taggable\Entity;

use DoctrineExtensions\Taggable\Taggable;

class Tag
{
    protected $id;
    protected $name;

    protected $createdAt;
    protected $updatedAt;

    protected $tagging;


    /**
     * Constructor
     *
     * @param string $name Tag's name
     */
    public function __construct($name=null)
    {
        $this->setName($name);
        $this->setCreatedAt(new \DateTime('now'));
        $this->setUpdatedAt(new \DateTime('now'));
    }

    /**
     * Returns tag's id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag's name
     *
     * @param string $name Name to set
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Returns tag's name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    public function setCreatedAt(\DateTime $date)
    {
        $this->createdAt = $date;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(\DateTime $date)
    {
        $this->updatedAt = $date;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
