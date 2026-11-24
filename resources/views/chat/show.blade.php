@extends('layouts.app')

@section('title', 'Chat - SPPG MART')

@section('content')
@php
    $otherUser = $user->isBuyer() ? $conversation->supplier : $conversation->buyer;
    $otherName = $user->isBuyer()
        ? ($otherUser->supplierProfile->business_name ?? $otherUser->name)
        : $otherUser->name;
    $callNumber = $otherPhone ? preg_replace('/[^0-9+]/', '', $otherPhone) : null;
@endphp
<div class="chat-ui mx-auto max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
    <div class="grid h-[calc(100dvh-13rem)] min-h-[480px] max-h-[820px] gap-0 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10 lg:grid-cols-[320px_minmax(0,1fr)]">
        <aside class="hidden min-h-0 border-r border-slate-200 bg-white lg:flex lg:flex-col">
            @include('chat._conversation-list', ['activeConversationId' => $conversation->id])
        </aside>

        <section class="flex min-h-0 min-w-0 flex-col">
            <header class="flex shrink-0 items-center gap-3 border-b border-slate-100 bg-white px-3 py-3 sm:px-5">
                <a href="{{ route('chat.index') }}" aria-label="Kembali ke semua chat" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-xl text-slate-500 hover:bg-slate-100 lg:hidden">←</a>
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-[#2187b5] to-[#105e7d] font-black text-white shadow-sm">{{ mb_substr($otherName, 0, 1) }}</span>
                <div class="min-w-0 flex-1">
                    <h1 class="truncate font-bold text-slate-900">{{ $otherName }}</h1>
                    <p class="truncate text-xs text-slate-500">{{ $user->isBuyer() ? 'Supplier' : 'Buyer' }} · Chat SPPG MART</p>
                </div>
                @if ($callNumber)
                    <a href="tel:{{ $callNumber }}" aria-label="Telepon {{ $otherName }}" title="Telepon {{ $otherName }}" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-[#126586] transition hover:bg-sky-50">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M7.2 3.5 9.5 7l-1.6 2a14 14 0 0 0 7.1 7.1l2-1.6 3.5 2.3-.6 3.2a2 2 0 0 1-2.2 1.6A17.5 17.5 0 0 1 2.4 6.3 2 2 0 0 1 4 4.1l3.2-.6Z"></path></svg>
                    </a>
                @else
                    <button type="button" disabled aria-label="Nomor telepon belum tersedia" title="Nomor telepon belum tersedia" class="flex h-10 w-10 shrink-0 cursor-not-allowed items-center justify-center rounded-full text-slate-300">
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-5 w-5"><path d="M7.2 3.5 9.5 7l-1.6 2a14 14 0 0 0 7.1 7.1l2-1.6 3.5 2.3-.6 3.2a2 2 0 0 1-2.2 1.6A17.5 17.5 0 0 1 2.4 6.3 2 2 0 0 1 4 4.1l3.2-.6Z"></path></svg>
                    </button>
                @endif
                <a href="{{ $user->isBuyer() ? route('suppliers.show', $otherUser) : route('transactions.index') }}" class="chat-brand-text hidden rounded-lg px-3 py-2 text-xs font-semibold hover:bg-[#eaf5fa] sm:inline-flex">
                    {{ $user->isBuyer() ? 'Lihat toko' : 'Pesanan' }}
                </a>
            </header>

            <div class="relative flex min-h-0 flex-1 flex-col bg-[#f1f8fc]">
                <div
                    id="chat-panel"
                    data-messages-url="{{ route('chat.messages.index', $conversation) }}"
                    data-last-id="{{ $messages->last()?->id ?? 0 }}"
                    class="min-h-0 flex-1 space-y-3 overflow-y-auto px-3 py-5 sm:px-6"
                    aria-live="polite"
                    aria-relevant="additions"
                >
                    <div class="mx-auto mb-4 w-fit rounded-full bg-white/80 px-3 py-1 text-[10px] font-medium text-slate-400 shadow-sm">Percakapan buyer dan supplier</div>
                    @forelse ($messages as $message)
                        <div class="flex {{ $message->sender_id === $user->id ? 'justify-end' : 'justify-start' }}" data-message-id="{{ $message->id }}">
                            <div class="max-w-[88%] rounded-2xl px-3.5 py-2.5 shadow-sm sm:max-w-[75%] sm:px-4 {{ $message->sender_id === $user->id ? 'chat-own-bubble rounded-br-md' : 'rounded-bl-md border border-slate-100 bg-white text-slate-800' }}">
                                @if ($message->sender_id !== $user->id)
                                    <p class="chat-brand-text mb-1 text-[11px] font-bold">{{ $message->sender->name }}</p>
                                @endif
                                @if (filled($message->body))
                                    <p class="whitespace-pre-wrap break-words text-sm leading-relaxed">{{ $message->body }}</p>
                                @endif
                                @if ($message->attachment_path)
                                    <a href="{{ route('chat.messages.attachment', [$conversation, $message]) }}" class="mt-2 block max-w-full overflow-hidden rounded-lg" aria-label="Buka lampiran {{ $message->attachment_name }}">
                                        @if (str_starts_with((string) $message->attachment_mime, 'image/'))
                                            <img src="{{ route('chat.messages.attachment', [$conversation, $message]) }}" alt="{{ $message->attachment_name }}" loading="lazy" class="max-h-64 max-w-full rounded-lg object-contain">
                                        @else
                                            <span class="flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-[#126586]">
                                                <span aria-hidden="true">📎</span>
                                                <span class="break-all">{{ $message->attachment_name }}</span>
                                            </span>
                                        @endif
                                    </a>
                                @endif
                                <p class="mt-1 text-right text-[10px] {{ $message->sender_id === $user->id ? 'chat-own-time' : 'text-slate-400' }}">{{ $message->created_at->format('H:i') }}</p>
                            </div>
                        </div>
                    @empty
                        <div id="chat-empty-state" class="mx-auto my-12 max-w-xs rounded-2xl border border-white bg-white/80 p-5 text-center shadow-sm">
                            <span class="chat-brand-soft chat-brand-text mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl">👋</span>
                            <p class="mt-3 text-sm font-bold text-slate-800">Mulai percakapan</p>
                            <p class="mt-1 text-xs leading-relaxed text-slate-500">Sampaikan pertanyaan tentang produk, stok, atau pesanan.</p>
                        </div>
                    @endforelse
                </div>
                <button id="new-messages" type="button" class="absolute bottom-4 left-1/2 hidden -translate-x-1/2 rounded-full bg-[#105e7d] px-4 py-2 text-xs font-bold text-white shadow-lg hover:bg-[#0c4e69]">Pesan baru ↓</button>
            </div>

            <div class="shrink-0 border-t border-slate-100 bg-white p-3 sm:px-5 sm:py-4">
                <p id="chat-status" class="mb-2 min-h-4 text-[11px] text-slate-400">Pesan diperbarui otomatis</p>
                <form id="chat-form" method="POST" action="{{ route('chat.messages.store', $conversation) }}" class="flex items-end gap-2">
                    @csrf
                    <input id="chat-attachment" name="attachment" type="file" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,image/jpeg,image/png,image/webp,application/pdf" class="hidden">
                    <div class="flex shrink-0 items-center gap-1">
                        <label for="chat-attachment" title="Lampirkan gambar atau dokumen" class="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-[#126586]">
                            <span aria-hidden="true" class="text-xl">📎</span>
                            <span class="sr-only">Lampirkan gambar atau dokumen</span>
                        </label>
                        <details id="chat-emoji-picker" class="relative">
                            <summary aria-label="Pilih emoji" title="Pilih emoji" class="flex h-10 w-10 cursor-pointer list-none items-center justify-center rounded-full text-xl transition hover:bg-slate-100">😊</summary>
                            <div class="absolute bottom-full left-0 z-10 mb-2 grid w-52 grid-cols-6 gap-1 rounded-xl border border-slate-200 bg-white p-2 shadow-xl">
                                @foreach (['😀', '😁', '😂', '😊', '😍', '🥰', '😉', '😎', '😭', '👍', '🙏', '👏', '❤️', '💙', '🎉', '✅', '🤝', '📦'] as $emoji)
                                    <button type="button" data-chat-emoji="{{ $emoji }}" class="flex h-8 w-8 items-center justify-center rounded-lg text-lg hover:bg-sky-50">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </details>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div id="chat-selected-file" class="mb-1 hidden items-center gap-2 text-xs text-slate-500">
                            <span class="min-w-0 flex-1 truncate"></span>
                            <button id="chat-remove-file" type="button" aria-label="Hapus lampiran" class="rounded px-1 text-slate-500 hover:bg-slate-100">✕</button>
                        </div>
                        <label for="chat-body" class="sr-only">Tulis pesan</label>
                        <textarea id="chat-body" name="body" rows="1" maxlength="2000" placeholder="Tulis pesan..." class="max-h-32 min-h-11 w-full resize-none rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-5 outline-none transition focus:border-[#2187b5] focus:bg-white focus:ring-4 focus:ring-[#e5f3fa]"></textarea>
                    </div>
                    <button id="chat-send" type="submit" disabled class="chat-brand-button flex h-11 shrink-0 items-center gap-2 rounded-2xl px-4 text-sm font-bold text-white shadow-sm transition disabled:cursor-not-allowed disabled:bg-slate-200 disabled:text-slate-400 disabled:shadow-none sm:px-5">
                        <span>Kirim</span><span aria-hidden="true">➤</span>
                    </button>
                </form>
                <p class="mt-2 hidden text-[10px] text-slate-400 sm:block">Enter untuk mengirim · Shift + Enter untuk baris baru</p>
                @error('body')
                    <p class="mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </section>
    </div>
</div>

<script>
    (() => {
        const panel = document.getElementById('chat-panel');
        const form = document.getElementById('chat-form');
        const input = document.getElementById('chat-body');
        const attachmentInput = document.getElementById('chat-attachment');
        const selectedFile = document.getElementById('chat-selected-file');
        const removeFileButton = document.getElementById('chat-remove-file');
        const emojiPicker = document.getElementById('chat-emoji-picker');
        const sendButton = document.getElementById('chat-send');
        const status = document.getElementById('chat-status');
        const newMessagesButton = document.getElementById('new-messages');
        let lastId = Number(panel.dataset.lastId);
        let polling = false;

        const isNearBottom = () => panel.scrollHeight - panel.clientHeight - panel.scrollTop < 96;

        const appendMessage = (message) => {
            const stickToBottom = message.own || isNearBottom();
            document.getElementById('chat-empty-state')?.remove();

            const row = document.createElement('div');
            row.className = `flex ${message.own ? 'justify-end' : 'justify-start'}`;
            row.dataset.messageId = message.id;

            const bubble = document.createElement('div');
            bubble.className = `max-w-[88%] rounded-2xl px-3.5 py-2.5 shadow-sm sm:max-w-[75%] sm:px-4 ${message.own ? 'chat-own-bubble rounded-br-md' : 'rounded-bl-md border border-slate-100 bg-white text-slate-800'}`;

            if (!message.own) {
                const sender = document.createElement('p');
                sender.className = 'chat-brand-text mb-1 text-[11px] font-bold';
                sender.textContent = message.sender_name;
                bubble.append(sender);
            }

            if (message.body) {
                const body = document.createElement('p');
                body.className = 'whitespace-pre-wrap break-words text-sm leading-relaxed';
                body.textContent = message.body;
                bubble.append(body);
            }

            if (message.attachment_url) {
                const attachment = document.createElement('a');
                attachment.href = message.attachment_url;
                attachment.className = 'mt-2 block max-w-full overflow-hidden rounded-lg';
                attachment.setAttribute('aria-label', `Buka lampiran ${message.attachment_name}`);

                if (message.attachment_is_image) {
                    const image = document.createElement('img');
                    image.src = message.attachment_url;
                    image.alt = message.attachment_name;
                    image.loading = 'lazy';
                    image.className = 'max-h-64 max-w-full rounded-lg object-contain';
                    attachment.append(image);
                } else {
                    const file = document.createElement('span');
                    file.className = 'flex items-center gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm font-medium text-[#126586]';
                    file.textContent = `📎 ${message.attachment_name}`;
                    attachment.append(file);
                }

                bubble.append(attachment);
            }

            const time = document.createElement('p');
            time.className = `mt-1 text-right text-[10px] ${message.own ? 'chat-own-time' : 'text-slate-400'}`;
            time.textContent = message.time;

            bubble.append(time);
            row.append(bubble);
            panel.append(row);
            lastId = Math.max(lastId, Number(message.id));

            if (stickToBottom) {
                panel.scrollTop = panel.scrollHeight;
                newMessagesButton.classList.add('hidden');
            } else {
                newMessagesButton.classList.remove('hidden');
            }
        };

        const updateSendButton = () => {
            sendButton.disabled = !input.value.trim() && !attachmentInput.files.length;
            input.style.height = 'auto';
            input.style.height = `${Math.min(input.scrollHeight, 128)}px`;
        };

        input.addEventListener('input', updateSendButton);
        attachmentInput.addEventListener('change', () => {
            const file = attachmentInput.files[0];
            if (file && file.size > 10 * 1024 * 1024) {
                attachmentInput.value = '';
                status.textContent = 'Ukuran lampiran maksimal 10 MB.';
            }

            const selected = attachmentInput.files[0];
            selectedFile.classList.toggle('hidden', !selected);
            selectedFile.classList.toggle('flex', Boolean(selected));
            selectedFile.querySelector('span').textContent = selected ? selected.name : '';
            updateSendButton();
        });

        removeFileButton.addEventListener('click', () => {
            attachmentInput.value = '';
            selectedFile.classList.add('hidden');
            selectedFile.classList.remove('flex');
            selectedFile.querySelector('span').textContent = '';
            updateSendButton();
        });

        emojiPicker.querySelectorAll('[data-chat-emoji]').forEach((button) => {
            button.addEventListener('click', () => {
                const start = input.selectionStart;
                const end = input.selectionEnd;
                input.setRangeText(button.dataset.chatEmoji, start, end, 'end');
                input.focus();
                emojiPicker.open = false;
                updateSendButton();
            });
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' && !event.shiftKey && !event.isComposing) {
                event.preventDefault();
                if (!sendButton.disabled) form.requestSubmit();
            }
        });

        newMessagesButton.addEventListener('click', () => {
            panel.scrollTop = panel.scrollHeight;
            newMessagesButton.classList.add('hidden');
            input.focus();
        });

        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if ((!input.value.trim() && !attachmentInput.files.length) || sendButton.disabled) return;

            sendButton.disabled = true;
            status.textContent = 'Mengirim pesan...';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: new FormData(form),
                });
                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.errors?.body?.[0] || data.errors?.attachment?.[0] || data.message || 'Pesan gagal dikirim.');
                }

                appendMessage(data.message);
                input.value = '';
                attachmentInput.value = '';
                selectedFile.classList.add('hidden');
                selectedFile.classList.remove('flex');
                selectedFile.querySelector('span').textContent = '';
                updateSendButton();
                status.textContent = 'Pesan terkirim';
            } catch (error) {
                status.textContent = error.message || 'Pesan gagal dikirim. Coba lagi.';
                sendButton.disabled = false;
            } finally {
                input.focus();
            }
        });

        const pollMessages = async () => {
            if (polling) return;
            polling = true;

            try {
                const response = await fetch(`${panel.dataset.messagesUrl}?after_id=${lastId}`, {
                    headers: { 'Accept': 'application/json' },
                });
                if (!response.ok) throw new Error('Koneksi terputus. Mencoba menghubungkan kembali...');

                const data = await response.json();
                data.messages.forEach(appendMessage);
                if (data.messages.length === 0 && !form.matches(':focus-within')) {
                    status.textContent = 'Pesan diperbarui otomatis';
                }
            } catch (error) {
                status.textContent = error.message || 'Koneksi terputus. Mencoba menghubungkan kembali...';
            } finally {
                polling = false;
            }
        };

        panel.scrollTop = panel.scrollHeight;
        updateSendButton();
        window.setInterval(pollMessages, 3000);
    })();
</script>
@endsection
