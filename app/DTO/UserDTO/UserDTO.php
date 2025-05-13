<?php

namespace App\DTO\UserDTO;

class UserDTO
{
    public int $id;
    public string $username;
    public string $email;
    public ?string $birthday;

    public function __construct(int $id, string $username, string $email, ?string $birthday = null)
    {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->birthday = $birthday;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'birthday' => $this->birthday,
        ];
    }
}