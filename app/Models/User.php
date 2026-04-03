<?php

namespace App\Models;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'phone', 'address', 'customer_type', 'ppui_number', 'email_verified_at', 'phone_verified_at'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'customer_type' => CustomerType::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function isCompanyCustomer(): bool
    {
        return $this->isCustomer() && $this->customer_type === CustomerType::Company;
    }

    public function isMuthowif(): bool
    {
        return $this->role === UserRole::Muthowif;
    }

    public function muthowifProfile(): HasOne
    {
        return $this->hasOne(MuthowifProfile::class);
    }

    /**
     * @return HasMany<MuthowifBooking, $this>
     */
    public function customerBookings(): HasMany
    {
        return $this->hasMany(MuthowifBooking::class, 'customer_id');
    }

    public function isVerifiedMuthowif(): bool
    {
        return $this->isMuthowif()
            && $this->muthowifProfile
            && $this->muthowifProfile->isApproved();
    }
}

