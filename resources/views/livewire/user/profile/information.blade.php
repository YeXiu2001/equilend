<div>
    <form id="update-profile" wire:submit="save" class="space-y-6">
        <!-- Profile Photo Drag & Drop -->
        <fieldset class="fieldset w-full"
            x-data="{
                isDragging: false,
                isUploading: false,
                progress: 0,
                clientError: '',
                maxBytes: 5 * 1024 * 1024,
                handleFiles(files) {
                    this.clientError = '';
                    if (! files || files.length === 0) {
                        return;
                    }
                    const file = files[0];
                    if (! file.type.startsWith('image/')) {
                        this.clientError = '{{ __('Please select a valid image file (PNG, JPG, GIF, WebP).') }}';
                        return;
                    }
                    if (file.size > this.maxBytes) {
                        this.clientError = '{{ __('Image size must not exceed 5MB.') }}';
                        return;
                    }
                    this.isUploading = true;
                    this.progress = 0;
                    $wire.upload(
                        'photo',
                        file,
                        () => {
                            this.isUploading = false;
                            this.progress = 100;
                        },
                        () => {
                            this.isUploading = false;
                            this.clientError = '{{ __('Failed to upload image. Please try again.') }}';
                        },
                        (event) => {
                            this.progress = event.detail.progress;
                        }
                    );
                }
            }"
            x-on:livewire-upload-start="isUploading = true; progress = 0"
            x-on:livewire-upload-finish="isUploading = false; progress = 100"
            x-on:livewire-upload-error="isUploading = false"
            x-on:livewire-upload-progress="progress = $event.detail.progress">
            <legend class="fieldset-legend">{{ __('Profile Photo') }}</legend>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-5 p-4 rounded-xl bg-base-200/40 border border-base-300">
                <!-- Avatar Preview -->
                <div class="flex flex-col items-center gap-2 shrink-0">
                    <div class="avatar {{ (! $photo && ! $user->pic_url) ? 'avatar-placeholder' : '' }}">
                        <div class="w-24 h-24 rounded-full ring-2 ring-base-300 shadow-xs overflow-hidden bg-base-200">
                            @if ($photo && method_exists($photo, 'isPreviewable') && $photo->isPreviewable())
                                <img src="{{ $photo->temporaryUrl() }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                            @elseif ($user->pic_url)
                                <img src="{{ $user->pic_url }}" alt="{{ $user->name }}" class="w-full h-full object-cover" />
                            @else
                                <div class="w-full h-full flex items-center justify-center font-bold text-3xl text-base-content/70">
                                    <span>{{ strtoupper(substr($user->name ?: 'U', 0, 1)) }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if ($photo)
                        <span class="text-[11px] font-semibold text-primary whitespace-nowrap">{{ __('New Preview') }}</span>
                    @endif
                </div>

                <!-- Dropzone -->
                <div class="flex-1 space-y-2.5">
                    <input type="file"
                        x-ref="photoInput"
                        accept="image/*"
                        class="hidden"
                        x-on:change="handleFiles($event.target.files); $event.target.value = ''" />

                    <div role="button"
                        tabindex="0"
                        x-on:click="$refs.photoInput.click()"
                        x-on:keydown.enter.prevent="$refs.photoInput.click()"
                        x-on:keydown.space.prevent="$refs.photoInput.click()"
                        x-on:dragover.prevent="isDragging = true"
                        x-on:dragleave.prevent="isDragging = false"
                        x-on:drop.prevent="isDragging = false; handleFiles($event.dataTransfer.files)"
                        x-bind:class="isDragging ? 'border-primary bg-primary/5' : 'border-base-300 hover:border-primary/50 hover:bg-base-100'"
                        class="border-2 border-dashed rounded-xl p-4 sm:p-5 text-center cursor-pointer transition-colors bg-base-100/70">
                        <div class="flex flex-col items-center justify-center gap-1.5">
                            <x-heroicon-o-cloud-arrow-up class="w-7 h-7 text-primary/80" />
                            <p class="text-xs sm:text-sm font-medium text-base-content">
                                {{ __('Drag & drop your image here, or') }}
                                <span class="text-primary font-semibold underline">{{ __('browse') }}</span>
                            </p>
                            <p class="text-[11px] text-base-content/60">
                                {{ __('PNG, JPG, GIF, or WebP • Max 5MB') }}
                            </p>
                        </div>

                        <!-- Upload Progress -->
                        <div x-show="isUploading" x-cloak class="mt-3 space-y-1">
                            <progress class="progress progress-primary w-full h-1.5" x-bind:value="progress" max="100"></progress>
                            <p class="text-[11px] text-primary font-medium" x-text="`{{ __('Uploading...') }} ${progress}%`"></p>
                        </div>
                    </div>

                    <p x-show="clientError" x-cloak x-text="clientError" class="text-error text-xs font-medium"></p>

                    @error('photo')
                        <p class="label text-error text-xs">{{ $message }}</p>
                    @enderror

                    <div class="flex flex-wrap items-center gap-2">
                        @if ($photo)
                            <button type="button"
                                wire:click="$set('photo', null)"
                                class="btn btn-ghost btn-xs gap-1">
                                <x-heroicon-o-x-mark class="w-3.5 h-3.5" />
                                <span>{{ __('Clear Selection') }}</span>
                            </button>
                        @endif

                        @if ($user->pic_dir)
                            <button type="button"
                                wire:click="removePhoto"
                                wire:confirm="{{ __('Are you sure you want to remove your profile photo?') }}"
                                class="btn btn-ghost btn-xs text-error gap-1">
                                <x-heroicon-o-trash class="w-3.5 h-3.5" />
                                <span>{{ __('Remove Current Photo') }}</span>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </fieldset>

        <!-- Account Details -->
        <div class="grid gap-4 sm:grid-cols-2 pt-2 border-t border-base-200">
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
                <input type="email" value="{{ $user->email }}" disabled class="input w-full bg-base-200/70 opacity-75 cursor-not-allowed" />
            </fieldset>
        </div>
    </form>

    <div class="mt-6 pt-4 border-t border-base-200 flex justify-end">
        <button type="submit" form="update-profile" class="btn btn-primary btn-sm px-5 font-semibold" wire:loading.attr="disabled" wire:target="save, photo">
            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
            <span>{{ __('Save Changes') }}</span>
        </button>
    </div>
</div>
