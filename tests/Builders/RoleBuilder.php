<?php

namespace Tests\Builders;

use App\Models\Role;

class RoleBuilder
{
    private static int $idCounter = 1;

    private string $title = 'server';

    public static function create(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function build(): Role
    {
        $role = new Role();

        $role->id = self::$idCounter++;
        $role->title = $this->title;

        return $role;
    }
}
