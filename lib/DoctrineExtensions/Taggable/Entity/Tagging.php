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

class Tagging
{
    protected $id;
    protected $tag;

    protected $resourceType;
    protected $resourceId;

    protected $createdAt;
    protected $updatedAt;


    /**
     * Constructor
     */
    public function __construct(Tag $tag = null, Taggable $resource = null)
    {
        if ($tag != null) {
            $this->setTag($tag);
        }

        if ($resource != null) {
            $this->setResource($resource);
        }

        $this->setCreatedAt(new \DateTime('now'));
        $this->setUpdatedAt(new \DateTime('now'));
    }

    /**
     * Returns tagging id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the tag object
     *
     * @param Tag $tag Tag to set
     */
    public function setTag(Tag $tag)
    {
        $this->tag = $tag;
    }

    /**
     * Returns the tag object
     *
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Sets the resource
     *
     * @param Taggable $resource Resource to set
     */
    public function setResource(Taggable $resource)
    {
        $this->resourceType = $resource->getTaggableType();
        $this->resourceId = $resource->getTaggableId();
    }

    /**
     * Returns the tagged resource type
     *
     * @return Taggable
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Returns the tagged resource id
     *
     * @return Taggable
     */
    public function getResourceId()
    {
        return $this->resourceId;
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
