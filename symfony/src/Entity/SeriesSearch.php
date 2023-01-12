<?php

namespace App\Entity;

use App\Entity\Genre;
use Doctrine\Common\Collections\ArrayCollection;

class SeriesSearch
{

    /**
     * @var string|null
     */
    private $titre;

    /**
     * @var ArrayCollection|null
     */
    private $genre;

    /**
     * @var \DateTime|null
     */
    private $date;

    private $Trier;

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): SeriesSearch
    {
        $this->titre = $titre;
        return $this;
    }

    public function getGenre(): ?Genre
    {
        return $this->genre;
    }

    public function setGenre(Genre $genre): SeriesSearch
    {
        $this->genre = $genre;
        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): SeriesSearch
    {
        $this->date = $date;
        return $this;
    }

    
    public function getTrier(): ?int
    {
        return $this->Trier;
    }
    
    public function setTrier(int $Trier): SeriesSearch
    {
        $this->Trier = $Trier;
        return $this;
    }
}