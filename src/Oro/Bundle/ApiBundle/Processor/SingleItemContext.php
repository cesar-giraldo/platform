<?php

namespace Oro\Bundle\ApiBundle\Processor;

class SingleItemContext extends Context
{
    /** an identifier of an entity */
    const ID = 'id';

    /**
     * Gets an identifier of an entity
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->get(self::ID);
    }

    /**
     * Sets an identifier of an entity
     *
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->set(self::ID, $id);
    }
}
