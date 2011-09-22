<?php

namespace DoctrineExtensions\Taggable\Entity;

use Tests\DoctrineExtensions\Taggable\Fixtures\TaggableObjectArticle as Article;
use DoctrineExtensions\Taggable\TagManager;
use DoctrineExtensions\Taggable\TagListener;

/**
 * @author Ryan Weaver <ryan@knplabs.com>
 */
class TagRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var \FPN\TagBundle\Entity\TagManager
     */
    protected $manager;

    public function setUp()
    {
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(__DIR__ . '/Proxy');
        $config->setProxyNamespace('DoctrineExtensions\Taggable\Proxies');

        $driverImpl = new \Doctrine\ORM\Mapping\Driver\DriverChain();
        $driverImpl->addDriver(new \Doctrine\ORM\Mapping\Driver\XmlDriver(__DIR__.'/../../../../metadata'), 'DoctrineExtensions\\Taggable\\Entity');
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
        ));

        $this->manager = new TagManager($this->em);
        $this->em->getEventManager()->addEventSubscriber(new TagListener($this->manager));
    }

    public function testGetTagsWithCountArray()
    {
        $this->loadFixtures();

        $tags = $this->getTagRepository()->getTagsWithCountArray('test-article');
        $this->assertEquals(array(
            'alltag' => 3,
            'tag3'   => 2,
            'tag1'   => 1,
            'tag2'   => 1,
        ), $tags);

        $tags = $this->getTagRepository()->getTagsWithCountArray('test-article', 2);
        $this->assertEquals(array('alltag' => 3, 'tag3' => 2), $tags);
    }

    public function testGetResourceIdsForTag()
    {
        $this->loadFixtures();

        // articles named tag3 and tag4 have the tag3 tag
        $tag3 = $this->getArticleRepository()->findOneBy(array('title' => 'My 3 article'));
        $tag4 = $this->getArticleRepository()->findOneBy(array('title' => 'My 4 article'));

        $ids = $this->getTagRepository()->getResourceIdsForTag('test-article', 'tag3');
        $this->assertEquals(array($tag3->getId(), $tag4->getId()), $ids);
    }

    /**
     * @return \DoctrineExtensions\Taggable\Entity\TagRepository
     */
    private function getTagRepository()
    {
        return $this->em
            ->getRepository('DoctrineExtensions\\Taggable\\Entity\\Tag')
        ;
    }

    /**
     * @return \Doctrine\ORM\EntityRepository
     */
    private function getArticleRepository()
    {
        return $this->em
            ->getRepository('Tests\\DoctrineExtensions\\Taggable\\Fixtures\\TaggableObjectArticle')
        ;
    }

    /**
     * Loads in some dummy articles with tags so we can test against it.
     *
     * Articles:
     *
     *  My 1 article => array(tag1, alltag)
     *  My 2 article => array(tag2, alltag)
     *  My 3 article => array(tag3, alltag)
     *  My 4 article => array(tag3)
     *
     *
     * @return void
     */
    private function loadFixtures()
    {
        $tagAll = $this->manager->loadOrCreateTag('alltag');
        $tags = array();
        $allArticles = array();

        for ($i = 1; $i <= 4; $i++) {
            $article = new Article();
            $article->setTitle('My '.$i.' article');
            $allArticles[] = $article;

            $this->em->persist($article);

            $tags[$i] = $this->manager->loadOrCreateTag('tag'.$i);
            $tags[$i]->setName('tag'.$i);

            if ($i != 4) {
                // give them their own tag and the all tag
                $article->getTags()->add($tags[$i]);
                $article->getTags()->add($tagAll);
            } else {
                // does't get its own tag, but get's 3's tag
                $article->getTags()->add($tags[3]);
            }
        }

        $this->em->flush();

        // save the tagging after everything's been flushed
        foreach ($allArticles as $article) {
            $this->manager->saveTagging($article);
        }
    }
}