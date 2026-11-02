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

                    <fieldset class="fieldset w-full sm:col-span-2">
                        <legend class="fieldset-legend">{{ __('Code / Identifier') }}</legend>
                        <input type="text" wire:model="code"
                            class="input w-full uppercase @error('code') input-error @enderror" />
                        @error('code')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    {{-- Typeahead Assign user --}}
                    <fieldset class="fieldset w-full sm:col-span-2">
                        <legend class="fieldset-legend">{{ __('Assign user') }}</legend>

                        @if ($this->selectedUser)
                            <div class="flex items-center justify-between p-2.5 rounded-xl border border-primary/30 bg-primary/5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="avatar avatar-placeholder shrink-0">
                                        <div class="bg-primary text-primary-content w-7 rounded-full text-xs font-bold">
                                            <span>{{ strtoupper(substr($this->selectedUser->name, 0, 1)) }}</span>
                                        </div>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-semibold text-base-content truncate">{{ $this->selectedUser->name }}</div>
                                        <div class="text-[11px] text-base-content/60 truncate">{{ $this->selectedUser->email }}</div>
                                    </div>
                                </div>
                                <button type="button" wire:click="clearAssignedUser" class="btn btn-ghost btn-xs text-error shrink-0">
                                    {{ __('Change') }}
                                </button>
                            </div>
                        @else
                            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                                <label class="input w-full flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 opacity-50 shrink-0">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                                    </svg>
                                    <input type="text"
                                        wire:model.live.debounce.300ms="user_search"
                                        @focus="open = true"
                                        @input="open = true"
                                        placeholder="{{ __('Search user by name or email...') }}"
                                        class="grow text-sm" />
                                    <span wire:loading wire:target="user_search" class="loading loading-spinner loading-xs text-primary shrink-0"></span>
                                </label>

                                <div x-show="open" x-cloak
                                    class="absolute left-0 right-0 mt-1 max-h-56 overflow-y-auto rounded-xl bg-base-100 border border-base-300 shadow-xl z-50 p-1">
                                    @forelse ($this->availableUsers as $user)
                                        <button type="button"
                                            wire:click="selectUser({{ $user->id }})"
                                            @click="open = false"
                                            class="w-full flex items-center justify-between p-2 rounded-lg text-left hover:bg-base-200 transition-colors">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="avatar avatar-placeholder shrink-0">
                                                    <div class="bg-base-300 text-base-content w-6 h-6 rounded-full text-[10px] font-bold">
                                                        <span>{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                                                    </div>
                                                </div>
                                                <div class="min-w-0">
                                                    <div class="text-xs font-semibold text-base-content truncate">{{ $user->name }}</div>
                                                    <div class="text-[11px] text-base-content/60 truncate">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                            <span class="text-[11px] text-primary font-medium shrink-0 ml-2">{{ __('Select') }}</span>
                                        </button>
                                    @empty
                                        <div class="p-3 text-center text-xs text-base-content/50">
                                            {{ __('No eligible users found') }}
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        @endif

                        @error('assigned_user_id')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Email') }}</legend>
                        <input type="email" wire:model="email"
                            class="input w-full @error('email') input-error @enderror" />
                        @error('email')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>

                    <fieldset class="fieldset w-full">
                        <legend class="fieldset-legend">{{ __('Phone') }}</legend>
                        <input type="text" wire:model="phone"
                            class="input w-full @error('phone') input-error @enderror" />
                        @error('phone')
                            <p class="label text-error text-xs">{{ $message }}</p>
                        @enderror
                    </fieldset>
                </div>

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
