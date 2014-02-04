<?php

namespace Tests\DoctrineExtensions\Taggable\Fixtures;

use DoctrineExtensions\Taggable\TaggableStringInterface;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 */
class TaggableStringArticle implements TaggableStringInterface
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

    protected $tagString;

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
        return 'test-article-with-tag-string';
    }

    public function getTaggableId()
    {
        return $this->getId();
    }

    public function getTagString()
    {
        return $this->tagString;
    }

    public function setTagString($tagString)
    {
        $this->tagString = $tagString;
    }
}
