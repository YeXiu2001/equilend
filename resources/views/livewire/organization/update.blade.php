<div>
    <dialog class="modal" @if ($modal) open @endif>
        <div class="modal-box max-w-xl">
            <h3 class="text-lg font-bold mb-4">{{ __('Edit Organization') }}</h3>

            <form id="org-update" wire:submit="save" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <fieldset class="fieldset w-full sm:col-span-2">
                        <legend class="fieldset-legend">{{ __('Organization Name') }} *</legend>
                        <input type="text" wire:model="name" required
                            class="input w-full @error('name') input-error @enderror" />
                        @error('name')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Code / Identifier') }}</legend>
                        <input type="text" wire:model="code"
                            class="input w-full uppercase @error('code') input-error @enderror" />
                        @error('code')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Assign Manager') }}</legend>
                        <select wire:model="assigned_user_id" class="select w-full @error('assigned_user_id') select-error @enderror">
                            <option value="">{{ __('-- Select Unassigned User --') }}</option>
                            @foreach ($this->availableUsers as $user)
                                <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                            @endforeach
                        </select>
                        @error('assigned_user_id')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Contact Email') }}</legend>
                        <input type="email" wire:model="email"
                            class="input w-full @error('email') input-error @enderror" />
                        @error('email')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Contact Phone') }}</legend>
                        <input type="text" wire:model="phone"
                            class="input w-full @error('phone') input-error @enderror" />
                        @error('phone')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Physical Address') }}</legend>
                    <input type="text" wire:model="address"
                        class="input w-full @error('address') input-error @enderror" />
                    @error('address')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <fieldset class="fieldset w-full">
                    <legend class="fieldset-legend">{{ __('Description / Notes') }}</legend>
                    <textarea wire:model="description" rows="2"
                        class="textarea w-full @error('description') textarea-error @enderror"></textarea>
                    @error('description')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror
                </fieldset>

                <div class="flex items-center gap-3 pt-1">
                    <input type="checkbox" id="update-is-active" wire:model="is_active" class="toggle toggle-primary" />
                    <label for="update-is-active" class="cursor-pointer text-sm font-medium">
                        {{ __('Active Status') }}
                    </label>
                </div>
            </form>

            <div class="modal-action">
                <button type="button" class="btn btn-ghost" wire:click="$set('modal', false)">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" form="org-update" class="btn btn-primary px-6" wire:loading.attr="disabled" wire:target="save">
                    <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                    <span>{{ __('Save Changes') }}</span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button type="button" wire:click="$set('modal', false)">close</button>
        </form>
    </dialog>
</div>
