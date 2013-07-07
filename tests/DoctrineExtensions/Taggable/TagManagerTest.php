<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/Fixtures/Article.php';

use DoctrineExtensions\Taggable\TagManager;
use DoctrineExtensions\Taggable\TagListener;
use DoctrineExtensions\Taggable\Entity\Tag;
use Tests\DoctrineExtensions\Taggable\Fixtures\Article;
use Doctrine\ORM\Tools\SchemaValidator;


class TagManagerTest extends \PHPUnit_Framework_TestCase
{
    protected $em;
    protected $manager;
    protected $article;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('DoctrineExtensions\Taggable\Proxies');
        //$config->setMetadataDriverImpl($config->newDefaultAnnotationDriver());

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
        $driverImpl->addDriver(new \Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__.'/../../../metadata'), 'DoctrineExtensions\\Taggable\\Entity');
        $driverImpl->addDriver($config->newDefaultAnnotationDriver(), 'Tests\\DoctrineExtensions\\Taggable\\Fixtures');
        $config->setMetadataDriverImpl($driverImpl);

        $this->em = \Doctrine\ORM\EntityManager::create(
            array('driver' => 'pdo_sqlite', 'memory' => true),
            $config
        );


        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $schemaTool->dropSchema(array());
        $schemaTool->createSchema(array(
            $this->em->getClassMetadata('DoctrineExtensions\\Taggable\\Entity\\Tag'),
            $this->em->getClassMetadata('DoctrineExtensions\\Taggable\\Entity\\Tagging'),
            $this->em->getClassMetadata('Tests\\DoctrineExtensions\\Taggable\\Fixtures\\Article'),
        ));

        $this->manager = new TagManager($this->em);
        $this->em->getEventManager()->addEventSubscriber(new TagListener($this->manager));
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::__construct
     */
    public function testConstructor()
    {
        $manager = new TagManager($this->em);
        $this->assertInstanceOf('DoctrineExtensions\Taggable\TagManager', $manager);
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::addTag
     */
    public function testAddTag()
    {
        $article = new Article();
        $article->setTitle('Test adding a tag...');

        $tag = new Tag('Doctrine2');
        $this->manager->addTag($tag, $article);

        $this->assertEquals(1, $article->getTags()->count());

        $firstTag = $article->getTags()->get(0);
        $this->assertInstanceOf('DoctrineExtensions\Taggable\Entity\Tag', $firstTag);
        $this->assertEquals($tag, $firstTag);
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::addTags
     */
    public function testAddTags()
    {
        $article = new Article();
        $article->setTitle('Test adding a tag...');

        $tag1 = new Tag('Doctrine2');
        $tag2 = new Tag('Symfony2');
        $tag3 = new Tag('PHP 5.3');
        $this->manager->addTags(array($tag1, 'tag', $tag2, $tag3), $article);

        $this->assertEquals(3, $article->getTags()->count());
        $this->assertEquals(array($tag1, $tag2, $tag3), $article->getTags()->toArray());

    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::removeTag
     */
    public function testRemoveTag()
    {
        $tag1 = new Tag('Doctrine2');
        $tag2 = new Tag('Testing');
        $tag3 = new Tag('Symfony2');

        $article = new Article();
        $article->setTitle('Test removing a tag...');

        $this->manager->addTag($tag1, $article);
        $this->manager->addTag($tag2, $article);
        $this->manager->addTag($tag3, $article);

        $this->assertEquals(3, $article->getTags()->count());

        $this->manager->removeTag($tag2, $article);
        $this->assertEquals(2, $article->getTags()->count());
        $this->assertEquals($tag1, $article->getTags()->get(0));
        $this->assertNull($article->getTags()->get(1));
        $this->assertEquals($tag3, $article->getTags()->get(2));

        $this->manager->removeTag($tag2, $article);
        $this->assertEquals(2, $article->getTags()->count());

        $this->manager->removeTag($tag1, $article);
        $this->assertEquals(1, $article->getTags()->count());

        $this->manager->removeTag($tag3, $article);
        $this->assertEquals(0, $article->getTags()->count());
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::replaceTags
     */
    public function testReplaceTags()
    {
        $tag1 = new Tag('Smallville');
        $tag2 = new Tag('Superman');
        $tag3 = new Tag('TV');

        $article = new Article();
        $article->setTitle('Test removing a tag...');

        $tags1 = array($tag1, $tag2, $tag3);
        $this->manager->addTags($tags1, $article);
        $this->assertEquals(3, $article->getTags()->count());
        $this->assertEquals($tags1, $article->getTags()->toArray());

        $tag4 = new Tag('Clark Kent');
        $tag5 = new Tag('Loïs Lane');

        $tags2 = array($tag1, $tag3, $tag4, $tag5);
        $this->manager->replaceTags($tags2, $article);
        $this->assertEquals(4, $article->getTags()->count());
        $this->assertEquals($tags2, $article->getTags()->toArray());
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::loadOrCreateTag
     */
    public function testLoadOrCreateTag()
    {
        $tag1 = $this->manager->loadOrCreateTag('Smallville');
        $this->assertInstanceOf('DoctrineExtensions\Taggable\Entity\Tag', $tag1);
        $this->assertGreaterThanOrEqual(1, $tag1->getId());
        $this->assertEquals('Smallville', $tag1->getName());

        $tag2 = $this->manager->loadOrCreateTag('Smallville');
        $this->assertEquals($tag1, $tag2);
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::loadOrCreateTags
     * @covers DoctrineExtensions\Taggable\TagManager::createTag
     */
    public function testLoadOrCreateTags()
    {
        $tagNames = array('Smallville', 'Superman', 'Smallville', 'TV');
        $tags = $this->manager->loadOrCreateTags($tagNames);

        $this->assertInternalType('array', $tags);
        $this->assertEquals(3, sizeof($tags));

        $this->assertInstanceOf('DoctrineExtensions\Taggable\Entity\Tag', $tags[0]);
        $this->assertGreaterThanOrEqual(1, $tags[0]->getId());
        $this->assertEquals('Smallville', $tags[0]->getName());

        $this->assertInstanceOf('DoctrineExtensions\Taggable\Entity\Tag', $tags[1]);
        $this->assertGreaterThanOrEqual(1, $tags[1]->getId());
        $this->assertEquals('Superman', $tags[1]->getName());

        $this->assertInstanceOf('DoctrineExtensions\Taggable\Entity\Tag', $tags[2]);
        $this->assertGreaterThanOrEqual(1, $tags[2]->getId());
        $this->assertEquals('TV', $tags[2]->getName());

        $this->assertEquals($tags, $this->manager->loadOrCreateTags($tagNames));


        $tagNames = array('Smallville');
        $this->assertEquals(array($tags[0]), $this->manager->loadOrCreateTags($tagNames));

        $this->assertEquals(array(), $this->manager->loadOrCreateTags(array()));
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::saveTagging
     * @covers DoctrineExtensions\Taggable\TagManager::loadTagging
     * @covers DoctrineExtensions\Taggable\TagManager::getTagging
     * @covers DoctrineExtensions\Taggable\TagManager::createTagging
     */
    public function testSaveLoadTagging()
    {
        $title = 'Testing...';
        $article = new Article();
        $article->setTitle($title);

        $this->em->persist($article);
        $this->em->flush();

        $tags1 = $this->manager->loadOrCreateTags(array('Smallville', 'Superman', 'TV'));
        $this->manager->addTags($tags1, $article);
        $this->manager->saveTagging($article);

        unset($article);
        $this->em->clear();

        $loadedArticle = $this->em
            ->getRepository('Tests\DoctrineExtensions\Taggable\Fixtures\Article')
            ->findOneBy(array('title' => $title));

        $this->assertNotNull($loadedArticle);
        $this->assertNotNull($loadedArticle->getTags());
        $this->assertEquals(0, $loadedArticle->getTags()->count());

        $this->manager->loadTagging($loadedArticle);
        $this->assertEquals(3, $loadedArticle->getTags()->count());
        $this->assertEquals($tags1[0]->getId(), $loadedArticle->getTags()->get(0)->getId());
        $this->assertEquals($tags1[1]->getId(), $loadedArticle->getTags()->get(1)->getId());
        $this->assertEquals($tags1[2]->getId(), $loadedArticle->getTags()->get(2)->getId());

        $article = $loadedArticle;
        $tags2 = $this->manager->loadOrCreateTags(array('Smallville', 'TV', 'Clark Kent', 'Loïs Lane'));
        $this->manager->replaceTags($tags2, $article);
        $this->manager->saveTagging($article);

        $this->manager->loadTagging($article);
        $this->assertEquals(4, $article->getTags()->count());
        $this->assertEquals($tags2[0]->getId(), $loadedArticle->getTags()->get(0)->getId());
        $this->assertEquals($tags2[1]->getId(), $loadedArticle->getTags()->get(1)->getId());
        $this->assertEquals($tags2[2]->getId(), $loadedArticle->getTags()->get(2)->getId());
        $this->assertEquals($tags2[3]->getId(), $loadedArticle->getTags()->get(3)->getId());
    }

    public function testDeleteResource()
    {
        $taggingRepository = $this->em
            ->getRepository('DoctrineExtensions\Taggable\Entity\Tagging');

        $tagRepository = $this->em
            ->getRepository('DoctrineExtensions\Taggable\Entity\Tag');

        $article = new Article();
        $article->setTitle('Testing...');

        $this->em->persist($article);
        $this->em->flush();

        $tags = $this->manager->loadOrCreateTags(array('Smallville', 'Superman', 'TV'));
        $this->manager->addTags($tags, $article);
        $this->manager->saveTagging($article);

        $this->assertEquals(3, sizeof($taggingRepository->findAll()));
        $this->assertEquals(3, sizeof($tagRepository->findAll()));

        $this->em->remove($article);
        $this->em->flush();

        $this->assertEquals(0, sizeof($taggingRepository->findAll()));
        $this->assertEquals(3, sizeof($tagRepository->findAll()));
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::splitTagNames
     */
    public function testSplitTagNames()
    {
        $this->assertEquals(array('Smallville'), $this->manager->splitTagNames('Smallville'));
        $this->assertEquals(array('Smallville'), $this->manager->splitTagNames(' Smallville '));

        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->splitTagNames('Smallville,Superman,TV'));
        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->splitTagNames(' Smallville, Superman    ,    TV   '));
        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->splitTagNames('Smallville , , Superman , TV'));

        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->splitTagNames(' Smallville Superman        TV   ', ' '));
    }

    /**
     * @covers DoctrineExtensions\Taggable\TagManager::getTagNames
     */
    public function testGetTagNames()
    {
        $article = new Article();
        $article->setTitle('Unit Test');

        $this->assertEquals(array(), $this->manager->getTagNames($article));

        $tag1 = new Tag('Smallville');
        $this->manager->addTag($tag1, $article);
        $this->assertEquals(array('Smallville'), $this->manager->getTagNames($article));

        $tag2 = new Tag('Superman');
        $tag3 = new Tag('TV');
        $this->manager->addTags(array($tag2, $tag3), $article);
        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->getTagNames($article));
    }

    /**
     * checks for valid schema
     */
    public function testValidSchema()
    {
        $validator = new SchemaValidator($this->em);
        $errors = $validator->validateClass($this->em->getClassMetadata('DoctrineExtensions\\Taggable\\Entity\\Tag'));
        
        if (count($errors) > 0) {
            // Lots of errors!
            echo PHP_EOL . implode("\n\n", $errors);
        }

        $this->assertEquals(0, count($errors));
    }


}
