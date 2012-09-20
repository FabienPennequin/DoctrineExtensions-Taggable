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
    
    /**
     * Constructor
     */
    public function __construct() 
    { 
        $this->data = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
    /**
     * Returns the $data ArrayCollection
     *
     * @return ArrayCollection
     */
    public function getData() 
    { 
        return $this->data;
    }
    
    /**
     * Adds tag metadata to the data ArrayCollection
     *
     * @param string $name Name of the Metadata field
     * @param mixed $value The value to store
     */
    public function add($name, $value)
    {
        $this->getData()->add(array("name" => $name, "value" => $value));
    } 
    
    /**
     * Returns the $data ArrayCollection
     *
     * @return ArrayCollection
     */
    public function dump() 
    { 
        return $this->data;
    }

}

?>