<?php

use function Livewire\Volt\{state, on};

state(['user', 'messages' => collect()]);

on(['echo-private:messages.{user},MessageCreated' => function (array $event) {
    $this->messages->prepend($event);
}])
?>

<div>
    @forelse($messages as $message)
        <div>{{ $message['message'] }} <span class="pl-6 text-gray-400 text-sm">{{ $message['created_at'] }}</span></div>
    @empty
        <div>empty</div>
    @endforelse
</div>
