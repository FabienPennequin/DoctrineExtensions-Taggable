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

    public function getId()
    {
        return $this->id;
    }

    public function setTitle($title)
    {
        return $this->title = $title;
    }

    public function getTaggableType()
    {
        return 'test-article';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }

    public function getTags()
    {
        $this->tags = $this->tags ?: new ArrayCollection();
        return $this->tags;
    }
}
