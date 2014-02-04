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
interface TaggableObjectInterface extends Taggable
{
    /**
     * Returns the collection of tags for this Taggable entity
     *
     * @return Doctrine\Common\Collections\Collection
     */
    function getTags();
}

