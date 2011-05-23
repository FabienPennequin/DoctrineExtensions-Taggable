<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 * (c) 2011 Fabien Pennequin <fabien@pennequin.me>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineExtensions\Taggable;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\LifecycleEventArgs;

/**
 * TagListener.
 *
 * @author Fabien Pennequin <fabien@pennequin.me>
 */
class TagListener implements EventSubscriber
{
    protected $manager;

    /**
     * Constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(TagManager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * @see Doctrine\Common\EventSubscriber
     */
    public function getSubscribedEvents()
    {
        return array(Events::preRemove);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function preRemove(LifecycleEventArgs $args)
    {
        if (($resource = $args->getEntity()) and $resource instanceof Taggable) {
            $this->manager->deleteTagging($resource);
        }
    }
}