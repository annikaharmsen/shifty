<?php

namespace Tests\Builders;

use App\Models\Company;
use App\Models\Role;

class RoleBuilder
{
    private string $title = 'server';
    private ?Company $company = null;

    public static function create(): self
    {
        return new self();
    }

    public function withTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function forCompany(Company $company): self
    {
        $this->company = $company;
        return $this;
    }

    public function build(): Role
    {
        // Create a default company if none provided
        if ($this->company === null) {
            $this->company = Company::create(['name' => 'Test Company']);
        }

        return Role::create([
            'company_id' => $this->company->id,
            'title' => $this->title,
        ]);
    }
}
