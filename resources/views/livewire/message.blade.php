<?php

use App\Events\MessageCreated;

use function Livewire\Volt\{state, on};

state(['user', 'messages' => collect(), 'message']);

on(['echo-private:messages.{user},MessageCreated' => function (array $event) {
    $this->messages->prepend($event);
}]);

$submit = function () {
    if (empty($this->message)) {
        return;
    }
    MessageCreated::dispatch(auth()->user(), $this->message);
    $this->message = '';
};
?>

<div>
    <x-text-input wire:model="message" wire:keydown.enter="submit"></x-text-input>
    <x-primary-button wire:click="submit" class="mb-3">送信</x-primary-button>

    @forelse($messages as $message)
        <div>{{ $message['message'] }} <span class="pl-6 text-gray-400 text-sm">{{ $message['created_at'] }}</span>
        </div>
    @empty
        <div>empty</div>
    @endforelse
</div>
