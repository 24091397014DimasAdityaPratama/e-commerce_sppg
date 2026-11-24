<section class="flex min-h-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white">
    <div class="border-b border-slate-100 p-4">
        <div class="flex items-center justify-between gap-3">
            <div>
                <p class="chat-brand-text text-xs font-bold uppercase tracking-[0.16em]">Pesan langsung</p>
                <h2 class="mt-1 text-xl font-black text-slate-900">Chat</h2>
            </div>
            <span class="chat-brand-soft chat-brand-text rounded-full px-3 py-1 text-xs font-bold">{{ $conversations->count() }} percakapan</span>
        </div>
        <label class="mt-4 flex items-center gap-2 rounded-xl bg-slate-100 px-3 py-2.5 text-slate-400 focus-within:ring-2 focus-within:ring-[#c8e6f4]">
            <span aria-hidden="true">⌕</span>
            <span class="sr-only">Cari percakapan</span>
            <input type="search" data-chat-search placeholder="Cari nama..." class="w-full bg-transparent text-sm text-slate-700 outline-none placeholder:text-slate-400">
        </label>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto">
        @forelse ($conversations as $chatConversation)
            @php
                $otherUser = $user->isBuyer() ? $chatConversation->supplier : $chatConversation->buyer;
                $otherName = $user->isBuyer()
                    ? ($otherUser->supplierProfile->business_name ?? $otherUser->name)
                    : $otherUser->name;
                $isActive = isset($activeConversationId) && $activeConversationId === $chatConversation->id;
            @endphp
            <a
                href="{{ route('chat.show', $chatConversation) }}"
                data-chat-item
                data-chat-name="{{ mb_strtolower($otherName) }}"
                class="flex items-center gap-3 border-b border-slate-100 px-4 py-3.5 transition {{ $isActive ? 'bg-[#eaf5fa]' : 'hover:bg-slate-50' }}"
            >
                <span class="relative flex h-12 w-12 shrink-0 items-center justify-center rounded-full {{ $isActive ? 'bg-[#167ba5] text-white' : 'chat-brand-soft chat-brand-text' }} font-black">
                    {{ mb_substr($otherName, 0, 1) }}
                    @if ($chatConversation->unread_count > 0)
                        <span class="absolute -right-0.5 -top-0.5 h-3 w-3 rounded-full border-2 border-white bg-sky-500"></span>
                    @endif
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center justify-between gap-2">
                        <span class="truncate text-sm font-bold text-slate-900">{{ $otherName }}</span>
                        @if ($chatConversation->last_message_at)
                            <span class="shrink-0 text-[10px] text-slate-400">{{ $chatConversation->last_message_at->format('H:i') }}</span>
                        @endif
                    </span>
                    <span class="mt-1 flex items-center justify-between gap-2">
                        <span class="truncate text-xs text-slate-500">{{ $chatConversation->latestMessage?->body ?? 'Mulai percakapan' }}</span>
                        @if ($chatConversation->unread_count > 0)
                            <span class="chat-brand-button flex h-5 min-w-5 shrink-0 items-center justify-center rounded-full px-1.5 text-[10px] font-bold text-white">{{ $chatConversation->unread_count }}</span>
                        @endif
                    </span>
                </span>
            </a>
        @empty
            <div class="px-5 py-10 text-center">
                <span class="chat-brand-soft chat-brand-text mx-auto flex h-12 w-12 items-center justify-center rounded-full text-xl">💬</span>
                <p class="mt-3 text-sm font-bold text-slate-800">Belum ada percakapan</p>
                <p class="mt-1 text-xs leading-relaxed text-slate-500">
                    {{ $user->isBuyer() ? 'Mulai chat dari halaman produk atau toko supplier.' : 'Chat dari buyer akan muncul di sini.' }}
                </p>
                @if ($user->isBuyer())
                    <a href="{{ route('catalog') }}" class="chat-brand-button mt-4 inline-flex rounded-lg px-4 py-2 text-xs font-bold text-white transition">Jelajahi produk</a>
                @endif
            </div>
        @endforelse
        <p data-chat-no-results class="hidden px-5 py-8 text-center text-sm text-slate-500">Tidak ada chat yang cocok.</p>
    </div>
</section>

<script>
    (() => {
        const search = document.querySelector('[data-chat-search]');
        if (!search) return;

        const items = [...document.querySelectorAll('[data-chat-item]')];
        const noResults = document.querySelector('[data-chat-no-results]');

        search.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            let visible = 0;

            items.forEach((item) => {
                const matches = item.dataset.chatName.includes(query);
                item.classList.toggle('hidden', !matches);
                if (matches) visible += 1;
            });

            noResults?.classList.toggle('hidden', visible > 0 || items.length === 0);
        });
    })();
</script>
