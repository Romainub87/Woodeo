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

    /**
     * @var int|null
     */
    private $Trier;

    /**
     * @var int|null
     */
    private $dateMin;

    /**
     * @var int|null
     */
    private $dateMax;

    /**
     * @var float|null
     */
    private $noteMin;

    /**
     * @var float|null
     */
    private $noteMax;

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

    public function getDateMin(): ?int
    {
        return $this->dateMin;
    }

    public function setDateMin(int $dateMin): SeriesSearch
    {
        $this->dateMin = $dateMin;
        return $this;
    }

    public function getDateMax(): ?int
    {
        return $this->dateMax;
    }

    public function setDateMax(int $dateMax): SeriesSearch
    {
        $this->dateMax = $dateMax;
        return $this;
    }

    public function getNoteMin(): ?float
    {
        return $this->noteMin;
    }

    public function setNoteMin(float $noteMin): SeriesSearch
    {
        $this->noteMin = $noteMin;
        return $this;
    }

    public function getNoteMax(): ?float
    {
        return $this->noteMax;
    }

    public function setNoteMax(float $noteMax): SeriesSearch
    {
        $this->noteMax = $noteMax;
        return $this;
    }

}