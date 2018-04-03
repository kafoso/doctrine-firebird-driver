<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="ALBUM")
 */
class Album
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private $timeCreated;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="Artist", inversedBy="albums", cascade={"persist"})
     */
    private $artist = null;

    /**
     * @ORM\ManyToMany(targetEntity="Song")
     * @ORM\JoinTable(
     *   name="Album_SongMap",
     *   joinColumns={
     *     @ORM\JoinColumn(name="album_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="song_id", referencedColumnName="id", unique=true)
     *   }
     * )
     */
    private $songs;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->timeCreated = new \DateTime;
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
            $song->addAlbum($this);
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
            $song->removeAlbum($this);
        }
        return $this;
    }

    /**
     * @param null|Artist $artist
     * @return self
     */
    public function setArtist(Artist $artist = null)
    {
        $previousArtist = $this->artist;
        $this->artist = $artist;
        if ($artist) {
            $artist->addAlbum($this);
        } elseif ($previousArtist) {
            $previousArtist->removeAlbum($this);
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
     * @return null|Artist
     */
    public function getArtist()
    {
        return $this->artist;
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
     * @return Collection
     */
    public function getSongs()
    {
        return $this->songs;
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getTimeCreated()
    {
        return \DateTimeImmutable::createFromMutable($this->timeCreated);
    }
}
