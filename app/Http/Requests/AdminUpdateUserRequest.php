<?php

namespace App\Http\Requests;

use App\Enums\CustomerType;
use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AdminUpdateUserRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->route('user');
        if (! $user instanceof User) {
            return [];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique(User::class)->ignore($user->id),
            ],
            'address' => ['nullable', 'string', 'max:2000'],
            'role' => ['required', Rule::enum(UserRole::class)],
            'customer_type' => ['nullable', Rule::enum(CustomerType::class)],
            'ppui_number' => ['nullable', 'string', 'max:64'],
            'locale' => ['nullable', 'string', Rule::in(['en', 'id'])],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');
        $this->merge([
            'phone' => is_string($phone) && $phone === '' ? null : $phone,
            'ppui_number' => is_string($this->input('ppui_number')) && $this->input('ppui_number') === ''
                ? null
                : $this->input('ppui_number'),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->route('user');
            if (! $user instanceof User) {
                return;
            }

            $role = $this->input('role');
            if (! is_string($role)) {
                return;
            }

            $newRole = UserRole::tryFrom($role);
            if (! $newRole) {
                return;
            }

            if ($user->id === $this->user()?->id && $newRole !== UserRole::Admin) {
                $validator->errors()->add('role', __('admin.users.cannot_demote_self'));
            }

            if ($user->isAdmin() && $newRole !== UserRole::Admin) {
                $adminCount = User::query()->where('role', UserRole::Admin)->count();
                if ($adminCount <= 1) {
                    $validator->errors()->add('role', __('admin.users.last_admin'));
                }
            }

            if ($newRole !== UserRole::Customer) {
                return;
            }

            $customerType = $this->input('customer_type');
            if ($customerType === null || $customerType === '') {
                $validator->errors()->add('customer_type', __('admin.users.customer_type_required'));
            }
        });
    }
}
