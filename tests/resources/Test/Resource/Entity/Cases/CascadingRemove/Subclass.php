<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity\Cases\CascadingRemove;

use Doctrine\ORM\Mapping as ORM;
use Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity\Cases\CascadingRemove;

/**
 * @ORM\Entity
 * @ORM\Table(name="CASES_CASCADINGREMOVE_SUBCLASS")
 */
class Subclass
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id = 1;

    /**
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }
}
