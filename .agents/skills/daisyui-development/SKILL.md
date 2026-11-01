---
name: daisyui-development
description: Build interfaces with daisyUI v5 component classes in a TALL Stack application (Tailwind CSS v4, Alpine.js, Laravel, Livewire) - using Context7 MCP for up-to-date daisyUI v5 documentation, semantic color tokens, responsive drawer sidebars, accessible dialogs, form fieldset patterns, and Livewire bindings. Use whenever the task touches UI components, layouts, forms, modals, tables, alerts, toasts, or themes with daisyUI.
---

# daisyUI v5 Development

daisyUI v5 is a CSS component library for Tailwind CSS v4 that provides semantic class names (`btn`, `card`, `input`, `modal`, `drawer`, `table`, `tabs`, `alert`, `toast`, etc.) and a CSS variable-based theme system.

## MCP Server (Context7)

Use the **Context7 MCP server** (`@upstash/context7-mcp`) to fetch up-to-date daisyUI v5 documentation and component examples whenever building or updating UI components.

Configuration in `.agents/mcp_config.json`:
```json
{
    "mcpServers": {
        "context7": {
            "type": "stdio",
            "command": "npx",
            "args": [
                "-y",
                "@upstash/context7-mcp@latest"
            ]
        }
    }
}
```

## Installation & Tailwind CSS v4 Setup

daisyUI v5 uses Tailwind CSS v4's CSS-first `@plugin` directive in `resources/css/app.css`:

```css
@import 'tailwindcss';

@source '../views';
@source '../../app';
@source '../../vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php';

@plugin 'daisyui' {
    themes: light --default, dark --prefersdark;
}
```

> **Important:** Do NOT use `@tailwindcss/forms` alongside daisyUI v5, as `@tailwindcss/forms` overrides native form element styles and conflicts with daisyUI's `.input`, `.select`, `.checkbox`, `.toggle`, and `.radio` classes.

## Core daisyUI v5 Component Patterns

### 1. Buttons (`btn`)

```blade
<button type="submit" class="btn btn-primary">Save</button>
<button type="button" class="btn btn-secondary btn-sm">Cancel</button>
<button type="button" class="btn btn-error btn-outline">Delete</button>
<button type="button" class="btn btn-ghost btn-circle">
    <!-- SVG icon -->
</button>
```

Modifiers:
- Colors: `btn-primary`, `btn-secondary`, `btn-accent`, `btn-info`, `btn-success`, `btn-warning`, `btn-error`, `btn-neutral`
- Styles: `btn-outline`, `btn-dash`, `btn-soft`, `btn-ghost`, `btn-link`
- Sizes: `btn-xs`, `btn-sm`, `btn-md` (default), `btn-lg`, `btn-xl`
- Shapes/Width: `btn-wide`, `btn-block`, `btn-square`, `btn-circle`

With Livewire loading states:
```blade
<button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="save">
    <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
    <span>{{ __('Save') }}</span>
</button>
```

### 2. Form Inputs & Fieldsets (`fieldset`, `input`, `select`, `checkbox`, `toggle`)

In daisyUI v5, `form-control` and `label-text` (from v4) are replaced by `fieldset`, `fieldset-legend`, and `label`:

```blade
<fieldset class="fieldset w-full">
    <legend class="fieldset-legend">{{ __('Email') }} *</legend>
    <input type="email" wire:model="email" class="input w-full @error('email') input-error @enderror" required />
    @error('email')
        <p class="label text-error text-xs">{{ $message }}</p>
    @enderror
</fieldset>
```

Checkboxes and Toggles:
```blade
<label class="label cursor-pointer justify-start gap-2">
    <input type="checkbox" name="remember" class="checkbox checkbox-primary checkbox-sm" />
    <span class="label-text text-sm">{{ __('Remember me') }}</span>
</label>
```

### 3. Cards (`card`)

```blade
<div class="card bg-base-100 card-border border-base-300 shadow-xs">
    <div class="card-body">
        <h2 class="card-title">{{ __('Card Title') }}</h2>
        <p>Card content goes here.</p>
        <div class="card-actions justify-end">
            <button class="btn btn-primary">Action</button>
        </div>
    </div>
</div>
```

### 4. Modals (`modal`)

With Livewire boolean state (`$modal`):
```blade
<dialog class="modal" @if ($modal) open @endif>
    <div class="modal-box">
        <h3 class="text-lg font-bold">{{ __('Modal Title') }}</h3>
        <div class="py-4">
            <!-- Modal content -->
        </div>
        <div class="modal-action">
            <button type="button" class="btn btn-ghost" wire:click="$set('modal', false)">{{ __('Cancel') }}</button>
            <button type="submit" form="my-form" class="btn btn-primary">{{ __('Save') }}</button>
        </div>
    </div>
    <form method="dialog" class="modal-backdrop">
        <button type="button" wire:click="$set('modal', false)">close</button>
    </form>
</dialog>
```

Or with Alpine / native HTML `<dialog>` API:
```blade
<dialog id="recovery-codes" class="modal">
    <div class="modal-box">
        <!-- Content -->
    </div>
    <form method="dialog" class="modal-backdrop">
        <button>close</button>
    </form>
</dialog>
```

### 5. Navigation & Layout (`drawer`, `navbar`, `menu`, `breadcrumbs`)

Use `drawer lg:drawer-open` for responsive admin layouts with a collapsible sidebar on mobile and persistent sidebar on desktop:

```blade
<div class="drawer lg:drawer-open">
    <input id="app-drawer" type="checkbox" class="drawer-toggle" />
    <div class="drawer-content flex flex-col min-h-screen bg-base-200">
        <!-- Navbar -->
        <header class="navbar bg-base-100 border-b border-base-300 px-4">
            <div class="flex-none lg:hidden">
                <label for="app-drawer" aria-label="open sidebar" class="btn btn-square btn-ghost">
                    <!-- Menu icon -->
                </label>
            </div>
            <div class="flex-1"></div>
            <div class="flex-none">
                <!-- Dropdown / Theme controller -->
            </div>
        </header>
        <!-- Main Content -->
        <main class="flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
    <div class="drawer-side z-40">
        <label for="app-drawer" aria-label="close sidebar" class="drawer-overlay"></label>
        <aside class="bg-base-100 border-r border-base-300 w-64 min-h-full">
            <ul class="menu w-full p-4">
                <li class="menu-title">Main Menu</li>
                <li><a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'menu-active' : '' }}">Dashboard</a></li>
            </ul>
        </aside>
    </div>
</div>
```

### 6. Tables (`table`)

```blade
<div class="overflow-x-auto">
    <table class="table table-zebra">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($this->rows as $row)
                <tr wire:key="row-{{ $row->id }}">
                    <td>{{ $row->id }}</td>
                    <td>{{ $row->name }}</td>
                    <td><!-- Actions --></td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
```

### 7. Alerts & Toasts (`alert`, `toast`)

```blade
<div role="alert" class="alert alert-success">
    <span>{{ $message }}</span>
</div>
```

Modals and Toasts can also be dispatched from Livewire via `App\Livewire\Traits\Alert`, which dispatches browser events handled by an Alpine listener in the main layout.

## Semantic Colors

Always prefer daisyUI semantic color classes so light and dark themes work automatically:
- Backgrounds: `bg-base-100`, `bg-base-200`, `bg-base-300`, `bg-primary`, `bg-secondary`, `bg-neutral`
- Text: `text-base-content`, `text-base-content/70`, `text-primary`, `text-error`, `text-success`
- Borders: `border-base-200`, `border-base-300`
