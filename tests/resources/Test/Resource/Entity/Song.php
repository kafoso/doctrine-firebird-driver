<?php
namespace Kafoso\DoctrineFirebirdDriver\Test\Resource\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="SONG")
 */
class Song
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
     * @ORM\ManyToMany(targetEntity="Album")
     * @ORM\JoinTable(
     *   name="Album_SongMap",
     *   joinColumns={
     *     @ORM\JoinColumn(name="song_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="album_id", referencedColumnName="id", unique=true)
     *   }
     * )
     */
    private $albums;

    /**
     * @ORM\ManyToOne(targetEntity="Genre", inversedBy="songs")
     */
    private $genre;

    /**
     * @ORM\ManyToOne(targetEntity="Artist", inversedBy="albums")
     */
    private $artist = null;

    /**
     * @param string $name
     */
    public function __construct($name, Genre $genre)
    {
        $this->timeCreated = new \DateTime;
        $this->setName($name);
        $this->setGenre($genre);
        $this->albums = new ArrayCollection;
    }

    /**
     * @return self
     */
    public function addAlbum(Album $album)
    {
        if (false == $this->albums->contains($album)) {
            $this->albums->add($album);
            $album->addSong($this);
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
            $album->removeSong($this);
        }
        return $this;
    }

    /**
     * @param null|Artist $artist
     * @return self
     */
    public function setArtist(Artist $artist = null)
    {
        $this->artist = $artist;
        return $this;
    }

    /**
     * @return self
     */
    public function setGenre(Genre $genre)
    {
        if ($this->genre) {
            $this->genre->removeSong($this);
        }
        $this->genre = $genre;
        $this->genre->addSong($this);
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
     * @return Collection
     */
    public function getAlbums()
    {
        return $this->albums;
    }

    /**
     * @return null|Artist
     */
    public function getArtist()
    {
        return $this->artist;
    }

    /**
     * @return Genre
     */
    public function getGenre()
    {
        return $this->genre;
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
     * @return \DateTimeImmutable
     */
    public function getTimeCreated()
    {
        return \DateTimeImmutable::createFromMutable($this->timeCreated);
    }
}
