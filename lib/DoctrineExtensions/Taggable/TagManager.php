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
     * @param array  $names   Array of tag names
     * @return Tag[]
     */
    public function loadOrCreateTags(array $names)
    {
        $names = array_unique($names);

        $builder = $this->em->createQueryBuilder();

        $tags = $builder
            ->select('t')
            ->from($this->tagClass, 't')

            ->where($builder->expr()->in('t.name', $names))

            ->getQuery()
            ->getResult()
        ;

        $loadedNames = array();
        foreach ($tags as $tag) {
            $loadedNames[] = $tag->getName();
        }

        $missingNames = array_diff($names, $loadedNames);
        if (sizeof($missingNames)) {
            foreach ($missingNames as $name) {
                $tag = $this->createTag($name);
                $this->em->persist($tag);

                $tags[] = $tag;
            }

            $this->em->flush();
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

        if ($oldTags != null and is_array($oldTags)) {
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
                    ->getQuery()
                    ->getResult()
                ;
            }
        }

        foreach ($tagsToAdd as $tag) {
            $this->em->persist($tag);
            $this->em->persist($this->createTagging($tag, $resource));
        }

        $this->em->flush();
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

            ->innerJoin('t.tagging', 't2', Expr\Join::WITH, 't2.resourceId = :id')
            ->setParameter('id', $resource->getResourceId())

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
            ->where('t.resourceId = :id')
            ->setParameter('id', $resource->getResourceId())
            ->getQuery()
            ->getResult();

        foreach ($taggingList as $tagging) {
            $this->em->remove($tagging);
        }
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
