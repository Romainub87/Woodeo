<?php
namespace App\Entity;

class UserSearch {

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var string|null
     */
    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     * @return UserSearch
     */
    public function setEmail(?string $email): UserSearch
    {
        $this->email = $email;
        return $this;
    }

}