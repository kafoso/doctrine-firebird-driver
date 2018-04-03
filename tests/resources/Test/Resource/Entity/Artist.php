<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ARTIST")
 */
class Artist
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity\Artist\Type", inversedBy="artists")
     */
    private $type;

    /**
     * @ORM\OneToMany(targetEntity="Album", mappedBy="artist")
     */
    private $albums;

    /**
     * @param string $name
     */
    public function __construct($name, Artist\Type $type)
    {
        $this->setName($name);
        $this->setType($type);
        $this->albums = new ArrayCollection;
    }

    /**
     * @return self
     */
    public function addAlbum(Album $album)
    {
        if (false == $this->albums->contains($album)) {
            $this->albums->add($album);
        }
        return $this;
    }

    /**
     * @return self
     */
    public function removeAlbum(Album $album)
    {
        if ($this->albums->contains($album)) {
            $this->albums->removeElement($album);
        }
        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return self
     */
    public function setType(Artist\Type $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return Collection                       Album[]
     */
    public function getAlbums()
    {
        return $this->albums;
    }

    /**
     * @return null|int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Artist\Type
     */
    public function getType()
    {
        return $this->type;
    }
}
