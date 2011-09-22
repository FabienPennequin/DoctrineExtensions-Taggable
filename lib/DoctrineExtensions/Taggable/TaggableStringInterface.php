<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineExtensions\Taggable;

/**
 * An optional interface that allows you to work with a tag string instead
 * of Tag objects
 *
 * @author Ryan Weaver <ryan@knplabs.com>
 */
interface TaggableStringInterface extends Taggable
{
    /**
     * Returns the comma-separated tag string
     *
     * @return string
     */
    function getTagString();

    /**
     * Sets the comma-separated tag string on this object
     *
     * @param string $tagString The comma-separated tags string
     */
    function setTagString($tagString);
}

