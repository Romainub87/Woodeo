<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping as ORM;
use phpDocumentor\Reflection\PseudoTypes\False_;
use phpDocumentor\Reflection\Types\Boolean;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints\Length;

/**
 * User
 *
 * @ORM\Table(name="user", uniqueConstraints={@ORM\UniqueConstraint(name="UNIQ_8D93D649E7927C74", columns={"email"})}, indexes={@ORM\Index(name="IDX_8D93D649F92F3E70", columns={"country_id"})})
 * @ORM\Entity
 */
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=128, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=128, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=128, nullable=false)
     */
    private $password;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="register_date", type="datetime", nullable=true)
     */
    private $registerDate;

    /**
     * @var bool
     *
     * @ORM\Column(name="admin", type="boolean", nullable=false)
     */
    private $admin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="user_id", type="string", length=128, nullable=true)
     */
    private $userId;

    /**
     * @var \Country
     *
     * @ORM\ManyToOne(targetEntity="Country")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="country_id", referencedColumnName="id")
     * })
     */
    private $country;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="following")
     */
    private $followers;
    // followers = ceux qui me follow
 
 
    /**
     * @ORM\ManyToMany(targetEntity="User", inversedBy="followers")
     * @ORM\JoinTable(name="following",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="following", referencedColumnName="id")}
     *      )
     */
    private $following;
    // following = ceux que je follow

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Episode", inversedBy="user")
     * @ORM\JoinTable(name="user_episode",
     *   joinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="episode_id", referencedColumnName="id")
     *   }
     * )
     */
    private $episode = array();

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Series", inversedBy="user")
     * @ORM\JoinTable(name="user_series",
     *   joinColumns={
     *     @ORM\JoinColumn(name="user_id", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="series_id", referencedColumnName="id")
     *   }
     * )
     */
    private $series = array();

    /**
     * @var \ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="Rating", mappedBy="user")
     */
    private $rates;

    /**
     * @ORM\Column(type="boolean")
     */
    private $suspended;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->episode = new \Doctrine\Common\Collections\ArrayCollection();
        $this->series = new \Doctrine\Common\Collections\ArrayCollection();
        $this->rates = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->following = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        if($email != null && str_contains($email, "@")){
            $this->email = $email;
        }
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function getClearPassword(): ?string
    {
        return password_verify($this->password,'auto');
    }

    public function setPassword(string $password): self
    {
        if(strlen($password) >= 5){
            $this->password = $password;
        }
        return $this;
    }

    public function getRegisterDate(): ?\DateTimeInterface
    {
        return $this->registerDate;
    }

    public function setRegisterDate(?\DateTimeInterface $registerDate): self
    {
        $this->registerDate = $registerDate;

        return $this;
    }

    public function isAdmin(): ?bool
    {
        return $this->admin;
    }

    public function setAdmin(bool $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getUserId(): ?string
    {
        return $this->userId;
    }

    public function setUserId(?string $userId): self
    {
        $this->userId = $userId;

        return $this;
    }

    public function getCountry(): ?Country
    {
        return $this->country;
    }

    public function setCountry(?Country $country): self
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return Collection<int, Episode>
     */
    public function getEpisode(): Collection
    {
        return $this->episode;
    }

    public function addEpisode(Episode $episode): self
    {
        if (!$this->episode->contains($episode)) {
            $this->episode->add($episode);
        }

        return $this;
    }

    public function removeEpisode(Episode $episode): self
    {
        $this->episode->removeElement($episode);

        return $this;
    }

    /**
     * @return Collection<int, Series>
     */
    public function getSeries(): Collection
    {
        return $this->series;
    }

    public function addSeries(Series $series): self
    {
        if (!$this->series->contains($series)) {
            $this->series->add($series);
        }

        return $this;
    }

    public function removeSeries(Series $series): self
    {
        $this->series->removeElement($series);

        return $this;
    }

    public function removeAllSeries(): self
    {
        $this->series->clear();

        return $this;
    }

    public function getAvancement(Series $series, EntityManager $em): int
    {
        $count = $em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(Episode::class, 'e')
            ->innerjoin('e.user', 'u')
            ->innerjoin('e.season', 'se')
            ->innerjoin('se.series', 's')
            ->where('u = :user')
            ->andWhere('s.id = :series')
            ->setParameter('user', $this)
            ->setParameter('series', $series->getId());
        return $count->getQuery()->getSingleScalarResult();
    }

    public function getUserIdentifier(): string { return $this->getEmail(); }
    public function getRoles(): array 
    { 
        if ($this->isAdmin()) { return ['ROLE_ADMIN']; }
        return ['ROLE_USER'];
    }
    public function eraseCredentials() { }

    public function __toString() { return (string)$this->id; }

    /**
     * @return Collection<int, Rating>
     */
    public function getRates(): Collection
    {
        return $this->rates;
    }

    public function addRate(Rating $rate): self
    {
        if (!$this->rates->contains($rate)) {
            $this->rates->add($rate);
            $rate->setUser($this);
        }

        return $this;
    }

    public function removeRate(Rating $rate): self
    {
        if ($this->rates->removeElement($rate)) {
            // set the owning side to null (unless already changed)
            if ($rate->getSeries() === $this) {
                $rate->setSeries(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(User $follower): self
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            $follower->addFollowing($this);
        }

        return $this;
    }

    public function removeFollower(User $follower): self
    {
        if ($this->followers->removeElement($follower)) {
            $follower->removeFollowing($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    public function addFollowing(User $following): self
    {
        if (!$this->following->contains($following)) {
            $this->following->add($following);
        }

        return $this;
    }

    public function removeFollowing(User $following): self
    {
        $this->following->removeElement($following);

        return $this;
    }

    public function removeAllFollowers(): self
    {
        foreach($this->getFollowers() as $f){
            $this->removeFollower($f);
        }
        return $this;
    }

    public function removeAllFollowings(): self
    {
        foreach($this->getFollowing() as $f){
            $this->removeFollowing($f);
        }
        return $this;
    }

    public function isSuspended(): ?bool
    {
        return $this->suspended;
    }

    public function setSuspended(bool $suspended): self
    {
        $this->suspended = $suspended;

        return $this;
    }

   
   

}
