@extends('layouts.app')

@section('title', 'Chat - SPPG MART')

@section('content')
<div class="chat-ui mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
    <div class="chat-hero mb-5 overflow-hidden rounded-2xl px-6 py-6 text-white shadow-lg sm:px-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-sky-100">SPPG MART · Pesan langsung</p>
        <h1 class="mt-2 text-3xl font-extrabold tracking-tight">Percakapan</h1>
        <p class="mt-1 text-sm text-sky-50">Hubungi buyer atau supplier dengan mudah.</p>
    </div>
    @include('chat._conversation-list', ['activeConversationId' => null])
</div>
@endsection
