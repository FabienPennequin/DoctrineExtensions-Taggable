<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__.'/../Fixtures/Article.php';

use DoctrineExtensions\Taggable\Entity\Tagging;
use DoctrineExtensions\Taggable\Entity\Tag;

use Tests\DoctrineExtensions\Taggable\Fixtures\Article;

class TaggingTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $tagging = new Tagging();
        $this->assertNull($tagging->getId());
        $this->assertEquals(new \DateTime('now'), $tagging->getCreatedAt());
        $this->assertEquals(new \DateTime('now'), $tagging->getUpdatedAt());

        $tag = new Tag('Smallville');

        $article = new Article();
        $article->setTitle('Hello World!');
        $article->id = 123;

        $tagging = new Tagging($tag, $article);
        $this->assertEquals($article->getResourceType(), $tagging->getResourceType());
        $this->assertEquals($article->getResourceId(), $tagging->getResourceId());
        $this->assertEquals($tag, $tagging->getTag());
    }

    /**
     * @covers DoctrineExtensions\Taggable\Entity\Tagging::setTag
     * @covers DoctrineExtensions\Taggable\Entity\Tagging::getTag
     */
    public function testSetGetTag()
    {
        $tag = new Tag('Doctrine2');

        $tagging = new Tagging();
        $tagging->setTag($tag);

        $this->assertEquals($tag, $tagging->getTag());
    }

    /**
     * @covers DoctrineExtensions\Taggable\Entity\Tagging::setResource
     * @covers DoctrineExtensions\Taggable\Entity\Tagging::getResourceId
     */
    public function testSetGetResource()
    {
        $article = new Article('Unit Testing');

        $tagging = new Tagging();
        $tagging->setResource($article);

        $this->assertEquals($article->getResourceType(), $tagging->getResourceType());
        $this->assertEquals($article->getResourceId(), $article->getResourceId());
    }
}
