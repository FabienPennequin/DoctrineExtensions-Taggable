<?php

namespace Tests\DoctrineExtensions\Taggable\Fixtures;

use DoctrineExtensions\Taggable\TaggableObjectInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class TaggableObjectArticle implements TaggableObjectInterface
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

    public function setTags(ArrayCollection $tags)
    {
        $this->tags = $tags;
    }
}
