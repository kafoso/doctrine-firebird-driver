<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity\Cases;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="CASES_CASCADINGREMOVE")
 */
class CascadingRemove
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     */
    private $id = 1;

    /**
     * @ORM\OneToOne(targetEntity="Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity\Cases\CascadingRemove\Subclass", cascade={"remove"})
     */
    private $subclass;

    public function __construct(CascadingRemove\Subclass $subclass)
    {
        $this->subclass = $subclass;
    }

    /**
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CascadingRemove\Subclass
     */
    public function getSubclass()
    {
        return $this->subclass;
    }
}
