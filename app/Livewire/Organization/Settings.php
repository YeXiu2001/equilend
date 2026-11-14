<?php

declare(strict_types=1);

namespace App\Livewire\Organization;

use Livewire\Component;
use App\Models\Organization;
use App\Models\PaymentMethod;
use App\Livewire\Traits\Alert;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class Settings extends Component
{
    use Alert;

    public ?Organization $organization = null;

    public ?int $selected_organization_id = null;

    // Organization Basic Details
    public string $name = '';

    public ?string $code = null;

    public ?string $email = null;

    public ?string $phone = null;

    public ?string $description = null;

    /**
     * Payment configurations keyed by payment_method_id.
     *
     * @var array<int, array{is_enabled: bool, account_name: string, account_numbers: list<string>}>
     */
    public array $paymentConfigs = [];

    public function mount(): void
    {
        $user = Auth::user();

        if (! $user || ! $user->hasAnyRole(['superadmin', 'organization_admin'])) {
            abort(403, 'Unauthorized access to organization settings.');
        }

        if ($user->hasRole('superadmin')) {
            // Superadmin can edit their own org or the first available organization
            $this->organization = $user->organization ?? Organization::first();
        } else {
            $this->organization = $user->organization;
        }

        if ($this->organization !== null) {
            $this->selected_organization_id = $this->organization->id;
            $this->loadOrganizationData();
        }
    }

    public function render(): View
    {
        return view('livewire.organization.settings');
    }

    #[Computed]
    public function allOrganizations(): Collection
    {
        return Organization::orderBy('name')->get();
    }

    #[Computed]
    public function availablePaymentMethods(): Collection
    {
        return PaymentMethod::where('is_active', true)->orderBy('name')->get();
    }

    public function changeOrganization(int $organizationId): void
    {
        if (! Auth::user()->hasRole('superadmin')) {
            return;
        }

        $org = Organization::find($organizationId);

        if ($org !== null) {
            $this->organization             = $org;
            $this->selected_organization_id = $org->id;
            $this->loadOrganizationData();
        }
    }

    public function loadOrganizationData(): void
    {
        if ($this->organization === null) {
            return;
        }

        $this->name        = $this->organization->name;
        $this->code        = $this->organization->code;
        $this->email       = $this->organization->email;
        $this->phone       = $this->organization->phone;
        $this->description = $this->organization->description;

        // Load payment configurations
        $existingConfigs = $this->organization->organizationPaymentMethods
            ->keyBy('payment_method_id');

        $this->paymentConfigs = [];

        foreach ($this->availablePaymentMethods as $pm) {
            $isCash = $pm->isCash();

            if ($existingConfigs->has($pm->id)) {
                $config                        = $existingConfigs->get($pm->id);
                $this->paymentConfigs[$pm->id] = [
                    'is_enabled'      => $isCash ? true : (bool) $config->is_enabled,
                    'account_name'    => (string) $config->account_name,
                    'account_numbers' => ! empty($config->account_numbers) ? array_values($config->account_numbers) : [''],
                ];
            } else {
                $this->paymentConfigs[$pm->id] = [
                    'is_enabled'      => $isCash,
                    'account_name'    => '',
                    'account_numbers' => [''],
                ];
            }
        }
    }

    public function addAccountNumber(int $paymentMethodId): void
    {
        if (! isset($this->paymentConfigs[$paymentMethodId])) {
            return;
        }

        $this->paymentConfigs[$paymentMethodId]['account_numbers'][] = '';
    }

    public function removeAccountNumber(int $paymentMethodId, int $index): void
    {
        if (! isset($this->paymentConfigs[$paymentMethodId]['account_numbers'][$index])) {
            return;
        }

        unset($this->paymentConfigs[$paymentMethodId]['account_numbers'][$index]);

        $this->paymentConfigs[$paymentMethodId]['account_numbers'] = array_values(
            $this->paymentConfigs[$paymentMethodId]['account_numbers']
        );

        // Ensure at least one input field remains
        if (empty($this->paymentConfigs[$paymentMethodId]['account_numbers'])) {
            $this->paymentConfigs[$paymentMethodId]['account_numbers'][] = '';
        }
    }

    public function rules(): array
    {
        $rules = [
            'name'        => ['required', 'string', 'max:255'],
            'code'        => ['nullable', 'string', 'max:20', Rule::unique('organizations', 'code')->ignore($this->organization?->id)],
            'email'       => ['nullable', 'string', 'email', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];

        $cashIds = $this->availablePaymentMethods
            ->filter(fn (PaymentMethod $pm): bool => $pm->isCash())
            ->pluck('id')
            ->all();

        foreach ($this->paymentConfigs as $pmId => $config) {
            if (in_array((int) $pmId, $cashIds, true)) {
                continue;
            }

            if (! empty($config['is_enabled'])) {
                $rules["paymentConfigs.{$pmId}.account_name"]      = ['required', 'string', 'max:255'];
                $rules["paymentConfigs.{$pmId}.account_numbers"]   = ['required', 'array', 'min:1'];
                $rules["paymentConfigs.{$pmId}.account_numbers.*"] = ['required', 'string', 'max:100'];
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [];

        foreach ($this->paymentConfigs as $pmId => $config) {
            $pm                                                            = PaymentMethod::find($pmId);
            $name                                                          = $pm ? $pm->name : 'Payment Method';
            $messages["paymentConfigs.{$pmId}.account_name.required"]      = "The {$name} account name is required when enabled.";
            $messages["paymentConfigs.{$pmId}.account_numbers.*.required"] = "Please fill in all {$name} account numbers or remove empty ones.";
        }

        return $messages;
    }

    public function save(): void
    {
        if ($this->organization === null) {
            $this->error('No organization assigned.');

            return;
        }

        $validated = $this->validate();

        // 1. Update Organization Details
        $this->organization->update([
            'name'        => $validated['name'],
            'code'        => $validated['code'] ? mb_strtoupper($validated['code']) : null,
            'email'       => $validated['email'],
            'phone'       => $validated['phone'],
            'description' => $validated['description'],
        ]);

        $cashIds = $this->availablePaymentMethods
            ->filter(fn (PaymentMethod $pm): bool => $pm->isCash())
            ->pluck('id')
            ->all();

        // 2. Update Payment Methods Configurations
        foreach ($this->paymentConfigs as $pmId => $config) {
            $isCash       = in_array((int) $pmId, $cashIds, true);
            $cleanNumbers = $isCash
                ? []
                : array_values(array_filter(
                    $config['account_numbers'],
                    fn ($num) => mb_trim((string) $num) !== ''
                ));

            $isEnabled = $isCash ? true : (bool) ($config['is_enabled'] ?? false);

            if ($isCash) {
                $this->paymentConfigs[$pmId]['is_enabled'] = true;
            }

            $this->organization->organizationPaymentMethods()->updateOrCreate(
                ['payment_method_id' => $pmId],
                [
                    'account_name'    => $isCash ? '' : ($config['account_name'] ?? ''),
                    'account_numbers' => $cleanNumbers,
                    'is_enabled'      => $isEnabled,
                ]
            );
        }

        $this->success('Organization settings and payment methods saved successfully.');
    }
}
