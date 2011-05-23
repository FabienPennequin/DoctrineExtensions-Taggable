<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use DoctrineExtensions\Taggable\Entity\Tag;

class TagTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $tag1 = new Tag();
        $this->assertNull($tag1->getId());
        $this->assertEquals(new \DateTime('now'), $tag1->getCreatedAt());
        $this->assertEquals(new \DateTime('now'), $tag1->getUpdatedAt());

        $tag2 = new Tag('Doctrine2');
        $this->assertNull($tag2->getId());
        $this->assertEquals('Doctrine2', $tag2->getName());
        $this->assertEquals(new \DateTime('now'), $tag2->getCreatedAt());
        $this->assertEquals(new \DateTime('now'), $tag2->getUpdatedAt());
    }

    /**
     * @covers DoctrineExtensions\Taggable\Entity\Tag::setName
     * @covers DoctrineExtensions\Taggable\Entity\Tag::getName
     */
    public function testSetGetName()
    {
        $tag = new Tag();
        $tag->setName('Doctrine');

        $this->assertEquals('Doctrine', $tag->getName());
    }
}
