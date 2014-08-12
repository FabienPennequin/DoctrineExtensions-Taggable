<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineExtensions\Taggable;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\Commons\Collections\Collection;
use DoctrineExtensions\Taggable\Entity\Tag;
use DoctrineExtensions\Taggable\Entity\Tagging;

/**
 * TagManager.
 *
 * @author Fabien Pennequin <fabien@pennequin.me>
 */
class TagManager
{
    protected $em;
    protected $tagClass;
    protected $taggingClass;

    public function __construct(EntityManager $em, $tagClass = null, $taggingClass = null)
    {
        $this->em = $em;

        $this->tagClass = $tagClass ?: 'DoctrineExtensions\Taggable\Entity\Tag';
        $this->taggingClass = $taggingClass ?: 'DoctrineExtensions\Taggable\Entity\Tagging';
    }

    /**
     * Adds a tag on the given taggable resource
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource
     */
    public function addTag(Tag $tag, Taggable $resource)
    {
        $resource->getTags()->add($tag);
    }

    /**
     * Adds multiple tags on the given taggable resource
     *
     * @param Tag[]     $tags       Array of Tag objects
     * @param Taggable  $resource   Taggable resource
     */
    public function addTags(array $tags, Taggable $resource)
    {
        foreach ($tags as $tag) {
            if ($tag instanceof Tag) {
                $this->addTag($tag, $resource);
            }
        }
    }

    /**
     * Removes an existant tag on the given taggable resource
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource
     * @return Boolean
     */
    public function removeTag(Tag $tag, Taggable $resource)
    {
        return $resource->getTags()->removeElement($tag);
    }

    /**
     * Replaces all current tags on the given taggable resource
     *
     * @param Tag[]     $tags       Array of Tag objects
     * @param Taggable  $resource   Taggable resource
     */
    public function replaceTags(array $tags, Taggable $resource)
    {
        $resource->getTags()->clear();
        $this->addTags($tags, $resource);
    }

    /**
     * Loads or creates a tag from tag name
     *
     * @param array  $name  Tag name
     * @return Tag
     */
    public function loadOrCreateTag($name)
    {
        $tags = $this->loadOrCreateTags(array($name));
        return $tags[0];
    }

    /**
     * Loads or creates multiples tags from a list of tag names
     *
     * @param array  $names          Array of tag names
     * @param bool   $persistMissing Determines if tags not present in DB should be
     * persisted. In filter Forms scenario you dont want to create tags that are 
     * used for filtering purpse only.
     * 
     * @return Tag[]
     */
    public function loadOrCreateTags(array $names, $persistMissing = true)
    {
        if (empty($names)) {
            return array();
        }
        $names = array_unique($names);
        
        $tags = $this->fetchTags($names);
        $missingNames = $this->computeMissingNames($names, $tags);
        $tags = array_merge($tags, $this->createTags($missingNames, $persistMissing));
        
        return $tags;
    }

    /**
     * Makes a DB query to get the list of tags by Tag names.
     * 
     * @param array $names Array of tag names
     * 
     * @return Tag[] The tags found in DB.
     */
    protected function fetchTags(array $names)
    {
        $builder = $this->em->createQueryBuilder();
        $tags = $builder
            ->select('t')
            ->from($this->tagClass, 't')

            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;
        return $tags;
    }
    
    /**
     * Gets the difference between desired tags and found tags.
     * 
     * @param array $names All desired Tag names.
     * @param array $tags  Tags found on DB.
     * 
     * @return array The missing names, that were not found in DB.
     */
    protected function computeMissingNames(array $names, array $tags)
    {
        $loadedNames = array();
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        return array_udiff($names, $loadedNames, 'strcasecmp');
    }
    
    /**
     * Create Tag objects as per $names list, optionally persistnign them to DB.
     * 
     * @param array $names   Tag names to set to new Tag objects.
     * @param bool  $persist Determines if created Tags must be persisted.
     * 
     * @return type
     */
    protected function createTags(array $names, $persist)
    {
        if (! sizeof($names)) {
            return array();
        }
        $tags = array();
        foreach ($names as $name) {
            $tag = $this->createTag($name);
            if ($persist) {
                $this->em->persist($tag);
                $this->em->flush($tag);
            }

            $tags[] = $tag;
        }
        
        if ($persist) {
            // TODO: remove this if, or bring flush() back here, as I moved it up
            // as flush()  with no params also flushes other managed dirty objects,
            // that might not be intended to be updated now.
        }
        return $tags;
    }
    
    /**
     * Saves tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function saveTagging(Taggable $resource)
    {
        $oldTags = $this->getTagging($resource);
        $newTags = $resource->getTags();
        $tagsToAdd = $newTags;

        if ($oldTags !== null and is_array($oldTags) and !empty($oldTags)) {
            $tagsToRemove = array();

            foreach ($oldTags as $oldTag) {
                if ($newTags->exists(function ($index, $newTag) use ($oldTag) {
                    return $newTag->getName() == $oldTag->getName();
                })) {
                    $tagsToAdd->removeElement($oldTag);
                } else {
                    $tagsToRemove[] = $oldTag->getId();
                }
            }

            if (sizeof($tagsToRemove)) {
                $builder = $this->em->createQueryBuilder();
                $builder
                    ->delete($this->taggingClass, 't')
                    ->where('t.tag_id')
                    ->where($builder->expr()->in('t.tag', $tagsToRemove))
                    ->andWhere('t.resourceType = :resourceType')
                    ->setParameter('resourceType', $resource->getTaggableType())
                    ->andWhere('t.resourceId = :resourceId')
                    ->setParameter('resourceId', $resource->getTaggableId())
                    ->getQuery()
                    ->getResult()
                ;
            }
        }

        foreach ($tagsToAdd as $tag) {
            $this->em->persist($tag);
            $this->em->persist($this->createTagging($tag, $resource));
        }

        if (count($tagsToAdd)) {
            $this->em->flush();
        }
    }

    /**
     * Loads all tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function loadTagging(Taggable $resource)
    {
        $tags = $this->getTagging($resource);
        $this->replaceTags($tags, $resource);
    }

    /**
     * Gets all tags for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    protected function getTagging(Taggable $resource)
    {
        return $this->em
            ->createQueryBuilder()

            ->select('t')
            ->from($this->tagClass, 't')

            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceId = :id AND t2.resourceType = :type')
            ->setParameter('id', $resource->getTaggableId())
            ->setParameter('type', $resource->getTaggableType())

            // ->orderBy('t.name', 'ASC')

            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Deletes all tagging records for the given taggable resource
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function deleteTagging(Taggable $resource)
    {
        $taggingList = $this->em->createQueryBuilder()
            ->select('t')
            ->from($this->taggingClass, 't')

            ->where('t.resourceType = :type')
            ->setParameter('type', $resource->getTaggableType())

            ->andWhere('t.resourceId = :id')
            ->setParameter('id', $resource->getTaggableId())

            ->getQuery()
            ->getResult();

        foreach ($taggingList as $tagging) {
            $this->em->remove($tagging);
        }
    }

    /**
     * Splits an string into an array of valid tag names
     *
     * @param string    $names      String of tag names
     * @param string    $separator  Tag name separator
     */
    public function splitTagNames($names, $separator=',')
    {
        $tags = explode($separator, $names);
        $tags = array_map('trim', $tags);
        $tags = array_filter($tags, function ($value) { return !empty($value); });

        return array_values($tags);
    }

    /**
     * Returns an array of tag names for the given Taggable resource.
     *
     * @param Taggable  $resource   Taggable resource
     */
    public function getTagNames(Taggable $resource)
    {
        $names = array();

        if (sizeof($resource->getTags()) > 0) {
            foreach ($resource->getTags() as $tag) {
                $names[] = $tag->getName();
            }
        }

        return $names;
    }

    /**
     * Creates a new Tag object
     *
     * @param string    $name   Tag name
     * @return Tag
     */
    protected function createTag($name)
    {
        return new $this->tagClass($name);
    }

    /**
     * Creates a new Tagging object
     *
     * @param Tag       $tag        Tag object
     * @param Taggable  $resource   Taggable resource object
     * @return Tagging
     */
    protected function createTagging(Tag $tag, Taggable $resource)
    {
        return new $this->taggingClass($tag, $resource);
    }
}
