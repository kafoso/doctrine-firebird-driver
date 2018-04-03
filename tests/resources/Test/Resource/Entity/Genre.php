<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="GENRE")
 */
class Genre
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
     * @ORM\OneToMany(targetEntity="Song", mappedBy="genre")
     */
    private $songs;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->setName($name);
        $this->songs = new ArrayCollection;
    }

    /**
     * @return self
     */
    public function addSong(Song $song)
    {
        if (false == $this->songs->contains($song)) {
            $this->songs->add($song);
            if ($this->getId() !== $song->getGenre()->getId()) {
                $song->setGenre($this);
            }
        }
        return $this;
    }

    /**
     * @return self
     */
    public function removeSong(Song $song)
    {
        if ($this->songs->contains($song)) {
            $this->songs->removeElement($song);
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
     * @return Collection                       Song[]
     */
    public function getSongs()
    {
        return $this->songs;
    }
}
