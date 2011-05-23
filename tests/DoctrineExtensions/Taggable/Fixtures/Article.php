<?php

namespace Tests\DoctrineExtensions\Taggable\Fixtures;

use DoctrineExtensions\Taggable\Taggable;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class Article implements Taggable
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @Column(name="title", type="string", length=50)
     */
    public $title;

    protected $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        return $this->title = $title;
    }

    public function getResourceId()
    {
        return 'test.article:'.$this->getId();
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();
        return $this->tags;
    }
}
