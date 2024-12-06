<?php
namespace App\DTO;

class DatabaseInfoDTO
{
    public string $database_name;

    public function __construct(string $database_name)
    {
        $this->database_name = $database_name;
    }
}
