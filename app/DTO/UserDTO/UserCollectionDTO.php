<?php

namespace App\DTO\UserDTO;

class UserCollectionDTO
{
    private array $users;

    public function __construct(array $users)
    {
        $this->users = $users;
    }

    /**
     * Преобразует коллекцию пользователей в массив для использования в API-ответах.
     * Если элемент коллекции — массив, он фильтруется и преобразуется в UserDTO.
     */
    public function toArray(): array
    {
        return array_map(function ($user) {
            // Если элемент — массив, фильтруем лишние ключи и создаем объект UserDTO
            if (is_array($user)) {
                $userData = array_intersect_key($user, array_flip(['id', 'username', 'email', 'birthday']));
                $user = new UserDTO(
                    $userData['id'] ?? null,
                    $userData['username'] ?? null,
                    $userData['email'] ?? null,
                    $userData['birthday'] ?? null
                );
            }
            return $user->toArray();
        }, $this->users);
    }
}