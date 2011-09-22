<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/Fixtures/TaggableObjectArticle.php';
require_once __DIR__.'/Fixtures/TaggableStringArticle.php';

use DoctrineExtensions\Taggable\TagManager;
use DoctrineExtensions\Taggable\TagListener;
use DoctrineExtensions\Taggable\Entity\Tag;
use Tests\DoctrineExtensions\Taggable\Fixtures\TaggableObjectArticle;
use Tests\DoctrineExtensions\Taggable\Fixtures\TaggableStringArticle;


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
            $this->em->getClassMetadata('Tests\\DoctrineExtensions\\Taggable\\Fixtures\\TaggableObjectArticle'),
            $this->em->getClassMetadata('Tests\\DoctrineExtensions\\Taggable\\Fixtures\\TaggableStringArticle'),
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

        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $tags);
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
        $this->assertEquals($tags[0], $this->manager->loadOrCreateTags($tagNames)->first());

        $this->assertEquals(0, count($this->manager->loadOrCreateTags(array())));
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
        $article = new TaggableObjectArticle();
        $article->setTitle($title);

        $this->em->persist($article);
        $this->em->flush();

        $tags1 = $this->manager->loadOrCreateTags(array('Smallville', 'Superman', 'TV'));
        $article->setTags($tags1);
        $this->manager->saveTagging($article);

        unset($article);
        $this->em->clear();

        $loadedArticle = $this->em
            ->getRepository('Tests\DoctrineExtensions\Taggable\Fixtures\TaggableObjectArticle')
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
        $article->setTags($tags2);
        $this->manager->saveTagging($article);

        $this->manager->loadTagging($article);
        $this->assertEquals(4, $article->getTags()->count());
        $this->assertEquals($tags2[0]->getId(), $loadedArticle->getTags()->get(0)->getId());
        $this->assertEquals($tags2[1]->getId(), $loadedArticle->getTags()->get(1)->getId());
        $this->assertEquals($tags2[2]->getId(), $loadedArticle->getTags()->get(2)->getId());
        $this->assertEquals($tags2[3]->getId(), $loadedArticle->getTags()->get(3)->getId());

        // test a TagStringInterface entity
        $title = 'Testing-tag-string';
        $article = new TaggableStringArticle();
        $article->setTitle($title);

        $this->em->persist($article);
        $this->em->flush();

        $article->setTagString('Foo, Bar');
        $this->manager->saveTagging($article);

        unset($article);
        $this->em->clear();

        $loadedArticle = $this->em
            ->getRepository('Tests\DoctrineExtensions\Taggable\Fixtures\TaggableStringArticle')
            ->findOneBy(array('title' => $title));

        $this->manager->loadTagging($loadedArticle);
        $this->assertEquals('Foo, Bar', $loadedArticle->getTagString());
    }

    public function testDeleteResource()
    {
        $taggingRepository = $this->em
            ->getRepository('DoctrineExtensions\Taggable\Entity\Tagging');

        $tagRepository = $this->em
            ->getRepository('DoctrineExtensions\Taggable\Entity\Tag');

        $article = new TaggableObjectArticle();
        $article->setTitle('Testing...');

        $this->em->persist($article);
        $this->em->flush();

        $tags = $this->manager->loadOrCreateTags(array('Smallville', 'Superman', 'TV'));
        $article->setTags($tags);
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
        $article = new TaggableObjectArticle();
        $article->setTitle('Unit Test');

        $this->assertEquals(array(), $this->manager->getTagNames($article));

        $tag1 = new Tag('Smallville');
        $article->getTags()->add($tag1);
        $this->assertEquals(array('Smallville'), $this->manager->getTagNames($article));

        $tag2 = new Tag('Superman');
        $tag3 = new Tag('TV');
        $article->getTags()->add($tag2);
        $article->getTags()->add($tag3);
        $this->assertEquals(array('Smallville', 'Superman', 'TV'), $this->manager->getTagNames($article));
    }

}
