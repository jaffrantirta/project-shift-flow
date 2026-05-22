<?php

namespace App\Filament\Pages\Auth;

use App\Models\Company;
use App\Models\User;
use Filament\Auth\Pages\Register as BaseRegister;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

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

    // Remove dehydrateStateUsing — User model's 'hashed' cast handles it
    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label(__('filament-panels::auth/pages/register.form.password.label'))
            ->password()
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->rule(Password::default())
            ->showAllValidationMessages()
            ->same('passwordConfirmation')
            ->validationAttribute(__('filament-panels::auth/pages/register.form.password.validation_attribute'));
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
        // Access Livewire state directly — $data from getState() may not include
        // custom fields that aren't part of the base Register schema
        $companyName = $this->data['company_name'] ?? $data['company_name'] ?? null;

        $company = Company::create([
            'name'     => $companyName,
            'slug'     => Str::slug($companyName ?? 'company') . '-' . Str::random(5),
            'timezone' => 'UTC',
        ]);

        return User::create([
            'company_id' => $company->id,
            'name'       => $data['name'],
            'email'      => $data['email'],
            'password'   => $data['password'],
            'role'       => 'admin',
            'status'     => 'active',
        ]);
    }
}
