<?php

namespace App\DTO\RoleDTO;

class RoleDTO
{
    public string $name;
    public string $slug;
    public ?string $description;
    public ?int $created_by;

    public function __construct(string $name, string $slug, ?string $description = null, ?int $created_by = null)
    {
        $this->name = $name;
        $this->slug = $slug;
        $this->description = $description;
        $this->created_by = $created_by;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'created_by' => $this->created_by,
        ];
    }
}