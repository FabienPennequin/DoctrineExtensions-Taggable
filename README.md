# Doctrine Extensions Taggable

This repository contains the Taggable extension for Doctrine 2. This allows to add
tags on your doctrine entities easily.


## Use


### Implement the DoctrineExtensions\Taggable\Taggable interface.

First, your entity must implement the `DoctrineExtensions\Taggable\Taggable` interface.
Three methods in your entity must be written:

 * `getTaggableType()` must return an unique name for your entity model
 * `getTaggableId()` must return an unique identifier for your entity
 * `getTags()` must return a doctrine collection (`Doctrine\Common\Collections\Collection`)


Example:

    namespace MyProject;

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
            return 'article';
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


### Setup Doctrine

Finally, you need to setup doctrine for register metadata directory and register TagListener.


First, register the metadata directory of this package.

    $config = new \Doctrine\ORM\Configuration();
    // ...
    $driverImpl = new \Doctrine\ORM\Mapping\Driver\XmlDriver(array('/path/to/doctrine-extensions-taggable/metadata'));
    $config->setMetadataDriverImpl($driverImpl);

or with `DriverChain`:

    $driverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
    // ...
    $driverImpl->addDriver(new \Doctrine\ORM\Mapping\Driver\XmlDriver('/path/to/doctrine-extensions-taggable/metadata'), 'DoctrineExtensions\\Taggable\\Entity');


Then, register the TagListener.

    // $this->em = EntityManager::create($connection, $config);
    // ...

    $this->tagManager = new TagManager($this->em);
    $this->em->getEventManager()->addEventSubscriber(new TagListener($this->tagManager));


### Using TagManager

Now, you can use TagManager.

    // Load or create a new tag
    $tag = $this->tagManager->loadOrCreateTag('Smallville');

    // Load or create a list of tags
    $tagNames = $this->tagManager->splitTagNames('Clark Kent, Loïs Lane, Superman'));
    $tags = $this->tagManager->loadOrCreateTags($tagNames);

    // Add a tag on your taggable resource..
    $this->tagManager->addTag($tag, $article);

    // Add a list of tags on your taggable resource..
    $this->tagManager->addTags($tags, $article);

    // Remove a tog on your taggable resource..
    $this->tagManager->remove($tag, $article);

    // Save tagging..
    // Note: $article must be saved in your database before (persist & flush)
    $this->tagManager->saveTagging($article);

    // Load tagging..
    $this->tagManager->loadTagging($article);

    // Replace all current tags..
    $tags = $this->tagManager->loadOrCreateTags(array('Smallville', 'Superman'));
    $this->tagManager->replaceTags($tags, $article);

### Tag-related queries

The Tag entity has a repository class, with two particularly helpful methods:

```php
<?php

    // somewhere crate or already have the entity manager
    // $em = EntityManager::create($connection, $config);

    $tagRepo = $em->getRepository('DoctrineExtensions\\Taggable\\Entity\\Tag');

    // find all article ids matching a particular query
    $ids = $tagRepo->getResourceIdsForTag('article_type', 'footag');

    // get the tags and count for all articles
    $tags = $tagRepo->getTagsWithCountArray('article_type');
    foreach ($tags as $name => $count) {
        echo sprintf('The tag "%s" matches "%s" articles', $name, $count);
    }
```