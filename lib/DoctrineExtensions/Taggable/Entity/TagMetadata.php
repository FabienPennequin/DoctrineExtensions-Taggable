<?php

/*
 * This file is part of the Doctrine Extensions Taggable package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DoctrineExtensions\Taggable\Entity;

use DoctrineExtensions\Taggable\Taggable;

class TagMetadata
{

    protected $data;
    
    public function __construct() 
    { 
        $this->data = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    public function getData() 
    { 
        return $this->data;
    }

    public function add($name, $value)
    {
        $this->getData()->add(array("name" => $name, "value" => $value));
    } 
    
    public function dump() 
    { 
        return $this->data;
    }

}

?>