<?php

declare(strict_types=1);

namespace App\Livewire\User\Profile;

use Exception;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithFileUploads;
use App\Livewire\Traits\Alert;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;

class Information extends Component
{
    use Alert;
    use WithFileUploads;

    public User $user;

    public mixed $photo = null;

    protected array $validationAttributes = [
        'user.name' => 'Name',
        'photo'     => 'Profile photo',
    ];

    #[On('mount')]
    public function mount(): void
    {
        $this->user = user();
    }

    public function rules(): array
    {
        return [
            'user.name' => [
                'required',
                'string',
                'max:255',
            ],
            'photo' => [
                'nullable',
                'image',
                'max:5120',
            ],
        ];
    }

    public function render(): View
    {
        return view('livewire.user.profile.information');
    }

    public function save(): void
    {
        $this->validate();

        try {
            if ($this->photo) {
                if ($this->user->pic_dir && Storage::disk('public')->exists($this->user->pic_dir)) {
                    Storage::disk('public')->delete($this->user->pic_dir);
                }

                $this->user->pic_dir = $this->photo->store('profiles', 'public');
                $this->photo         = null;
            }

            $this->user->save();

            $this->dispatch('updated', name: $this->user->name, pic_dir: $this->user->pic_url);
            $this->success('Profile updated successfully.');

            return;
        } catch (Exception $e) {
            report($e);
        }

        $this->error();
    }

    public function removePhoto(): void
    {
        try {
            if ($this->user->pic_dir && Storage::disk('public')->exists($this->user->pic_dir)) {
                Storage::disk('public')->delete($this->user->pic_dir);
            }

            $this->user->pic_dir = null;
            $this->user->save();
            $this->photo = null;

            $this->dispatch('updated', name: $this->user->name, pic_dir: null);
            $this->success('Profile photo removed.');

            return;
        } catch (Exception $e) {
            report($e);
        }

        $this->error();
    }
}
