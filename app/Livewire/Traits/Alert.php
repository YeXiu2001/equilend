<?php

declare(strict_types=1);

namespace App\Livewire\Traits;

use Livewire\Component;

trait Alert
{
    public function success(string $description = 'Task completed successfully.', string $title = 'Done!'): void
    {
        $this->dispatch('alert:dialog', type: 'success', title: __($title), description: __($description));
    }

    public function error(string $description = 'Something went wrong!', string $title = 'Ops!'): void
    {
        $this->dispatch('alert:dialog', type: 'error', title: __($title), description: __($description));
    }

    public function warning(string $description = 'Hey! This is dangerous.', string $title = 'Ops!'): void
    {
        $this->dispatch('alert:dialog', type: 'warning', title: __($title), description: __($description));
    }

    public function info(string $description = 'Ops! Pay attention on it.', string $title = 'Warning!'): void
    {
        $this->dispatch('alert:dialog', type: 'info', title: __($title), description: __($description));
    }

    public function question(string $description = 'Are you sure?', string $title = 'Warning!'): object
    {
        return new class($this, __($title), __($description))
        {
            private ?string $confirmMethod = null;

            private mixed $confirmParams = null;

            public function __construct(
                private readonly Component $component,
                private readonly string $title,
                private readonly string $description,
            ) {}

            public function confirm(?string $text = null, ?string $method = null, mixed $params = null): self
            {
                $this->confirmMethod = $method;
                $this->confirmParams = $params;

                return $this;
            }

            public function cancel(?string $text = null, ?string $method = null, mixed $params = null): self
            {
                return $this;
            }

            public function send(): void
            {
                $this->component->dispatch(
                    'alert:dialog',
                    type: 'question',
                    title: $this->title,
                    description: $this->description,
                    componentId: $this->component->getId(),
                    confirmMethod: $this->confirmMethod,
                    confirmParams: $this->confirmParams,
                );
            }
        };
    }
}
