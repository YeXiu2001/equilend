<div>
    <form id="update-profile" wire:submit="save" class="space-y-6">
        <!-- Profile Photo -->
        <fieldset class="fieldset w-full">
            <legend class="fieldset-legend">{{ __('Profile Photo') }}</legend>
            <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4">
                <div class="avatar shrink-0 {{ (! $photo && ! $user->pic_url) ? 'avatar-placeholder' : '' }}">
                    <div class="w-20 h-20 rounded-full ring-2 ring-base-300 shadow-sm overflow-hidden bg-base-200">
                        @if ($photo && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                            <img src="{{ $photo->temporaryUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                        @elseif ($user->pic_url)
                            <img src="{{ $user->pic_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                        @else
                            <div class="w-full h-full flex items-center justify-center font-bold text-2xl text-base-content/70">
                                <span>{{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="space-y-2 flex-1 w-full max-w-md">
                    <input type="file" wire:model="photo" accept="image/*" class="file-input file-input-bordered file-input-sm w-full @error('photo') file-input-error @enderror" />
                    <div wire:loading wire:target="photo" class="text-xs text-primary flex items-center gap-1.5">
                        <span class="loading loading-spinner loading-xs"></span>
                        <span>{{ __('Uploading preview...') }}</span>
                    </div>
                    @error('photo')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror

                    @if ($user->pic_dir)
                        <div>
                            <button type="button" wire:click="removePhoto" wire:confirm="{{ __('Are you sure you want to remove your profile photo?') }}" class="btn btn-ghost btn-xs text-error gap-1">
                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                <span>{{ __('Remove Photo') }}</span>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </fieldset>

        <div class="grid gap-4 sm:grid-cols-2">
            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Name') }} *</legend>
                <input type="text" wire:model="user.name" required
                    class="input w-full @error('user.name') input-error @enderror" />
                @error('user.name')
                    <p class="label text-error text-xs">{{ $message }}</p>
                @enderror
            </fieldset>

            <fieldset class="fieldset w-full">
                <legend class="fieldset-legend">{{ __('Email') }}</legend>
                <input type="email" value="{{ $user->email }}" disabled class="input w-full opacity-70" />
            </fieldset>
        </div>
    </form>

    <div class="mt-6 flex items-center justify-between">
        <livewire:user.profile.delete />

        <button type="submit" form="update-profile" class="btn btn-primary rounded-full" wire:loading.attr="disabled" wire:target="save, photo">
            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
            <span>{{ __('Save') }}</span>
        </button>
    </div>
</div>
