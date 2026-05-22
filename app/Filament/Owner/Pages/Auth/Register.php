<?php

namespace App\Filament\Owner\Pages\Auth;

use App\Models\Company;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Register extends BaseRegister
{
    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getCompanyNameFormComponent(),
                $this->getNameFormComponent(),
                $this->getEmailFormComponent(),
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }

    protected function getCompanyNameFormComponent(): Component
    {
        return TextInput::make('company_name')
            ->label('Company Name')
            ->placeholder('e.g. Acme Corp')
            ->required()
            ->maxLength(255)
            ->autofocus();
    }

    protected function getNameFormComponent(): Component
    {
        return TextInput::make('name')
            ->label('Your Full Name')
            ->required()
            ->maxLength(255);
    }

    public function getHeading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Create your account';
    }

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return 'Start managing your workforce with ShiftFlow.';
    }

    protected function handleRegistration(array $data): Model
    {
        $company = Company::create([
            'name'     => $data['company_name'],
            'slug'     => Str::slug($data['company_name']) . '-' . Str::random(5),
            'timezone' => 'UTC',
        ]);

        return User::create([
            'company_id' => $company->id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'role'       => 'owner',
            'is_owner'   => true,
            'status'     => 'active',
        ]);
    }
}
