<?php

namespace App\Http\Controllers;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ChatController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        abort_unless($user->isBuyer() || $user->isSupplier(), 403);

        $conversations = $this->conversationsFor($user)->get();

        return view('chat.index', compact('conversations', 'user'));
    }

    public function startWithSupplier(Request $request, User $supplier)
    {
        abort_unless($request->user()->isBuyer(), 403);
        abort_unless($supplier->isSupplier(), 404);

        $conversation = ChatConversation::firstOrCreate([
            'buyer_id' => $request->user()->id,
            'supplier_id' => $supplier->id,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function startForOrder(Request $request, Order $order)
    {
        $user = $request->user();
        abort_unless(
            ($user->isBuyer() && $order->buyer_id === $user->id)
            || ($user->isSupplier() && $order->supplier_id === $user->id),
            403
        );

        $conversation = ChatConversation::firstOrCreate([
            'buyer_id' => $order->buyer_id,
            'supplier_id' => $order->supplier_id,
        ]);

        return redirect()->route('chat.show', $conversation);
    }

    public function show(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        $user = $request->user();
        $conversation->load(['buyer', 'supplier']);
        $otherUser = $user->isBuyer() ? $conversation->supplier : $conversation->buyer;
        $otherPhone = $otherUser->phone ?: ($otherUser->isBuyer()
            ? $otherUser->addresses()->orderByDesc('is_primary')->latest()->value('phone')
            : null);
        $conversations = $this->conversationsFor($user)->get();
        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->latest('id')
            ->take(50)
            ->get()
            ->reverse()
            ->values();

        $conversation->messages()
            ->where('sender_id', '!=', $user->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return view('chat.show', compact('conversation', 'conversations', 'messages', 'user', 'otherPhone'));
    }

    public function messages(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        $validated = $request->validate([
            'after_id' => ['nullable', 'integer', 'min:0'],
        ]);

        $conversation->messages()
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = $conversation->messages()
            ->with('sender:id,name')
            ->when(
                isset($validated['after_id']),
                fn ($query) => $query->where('id', '>', $validated['after_id'])
            )
            ->orderBy('id')
            ->limit(50)
            ->get()
            ->map(fn ($message) => $this->formatMessage($message, $request));

        return response()->json(['messages' => $messages]);
    }

    public function send(Request $request, ChatConversation $conversation)
    {
        $this->authorizeParticipant($request, $conversation);
        $validated = $request->validate([
            'body' => ['nullable', 'required_without:attachment', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt', 'max:10240'],
        ]);
        $attachment = $request->file('attachment');
        $attachmentPath = $attachment?->store('chat-attachments/'.$conversation->id, 'local');

        if ($attachment && ! $attachmentPath) {
            throw new \RuntimeException('Lampiran chat gagal disimpan.');
        }

        $message = DB::transaction(function () use ($request, $conversation, $validated, $attachment, $attachmentPath) {
            $message = $conversation->messages()->create([
                'sender_id' => $request->user()->id,
                'body' => trim($validated['body'] ?? ''),
                'attachment_path' => $attachmentPath,
                'attachment_name' => $attachment?->getClientOriginalName(),
                'attachment_mime' => $attachment?->getMimeType(),
                'attachment_size' => $attachment?->getSize(),
            ]);

            $conversation->update(['last_message_at' => $message->created_at]);

            return $message;
        });
        $message->load('sender:id,name');

        if ($request->expectsJson()) {
            return response()->json(['message' => $this->formatMessage($message, $request)], 201);
        }

        return redirect()->route('chat.show', $conversation);
    }

    public function attachment(Request $request, ChatConversation $conversation, ChatMessage $message)
    {
        $this->authorizeParticipant($request, $conversation);
        abort_unless($message->chat_conversation_id === $conversation->id && $message->attachment_path, 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($message->attachment_path), 404);

        $isImage = str_starts_with((string) $message->attachment_mime, 'image/');

        return $disk->response(
            $message->attachment_path,
            $message->attachment_name,
            [
                'Content-Type' => $message->attachment_mime ?: 'application/octet-stream',
                'X-Content-Type-Options' => 'nosniff',
            ],
            $isImage ? 'inline' : 'attachment'
        );
    }

    private function formatMessage($message, Request $request): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender->name,
            'own' => $message->sender_id === $request->user()->id,
            'time' => $message->created_at->format('H:i'),
            'attachment_url' => $message->attachment_path
                ? route('chat.messages.attachment', [$message->chat_conversation_id, $message->id])
                : null,
            'attachment_name' => $message->attachment_name,
            'attachment_mime' => $message->attachment_mime,
            'attachment_size' => $message->attachment_size,
            'attachment_is_image' => str_starts_with((string) $message->attachment_mime, 'image/'),
        ];
    }

    private function authorizeParticipant(Request $request, ChatConversation $conversation): void
    {
        $user = $request->user();
        abort_unless(
            ($user->isBuyer() && $conversation->buyer_id === $user->id)
            || ($user->isSupplier() && $conversation->supplier_id === $user->id),
            403
        );
    }

    private function conversationsFor(User $user)
    {
        return ChatConversation::query()
            ->where($user->isBuyer() ? 'buyer_id' : 'supplier_id', $user->id)
            ->with(['buyer', 'supplier.supplierProfile', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn ($query) => $query
                ->whereNull('read_at')
                ->where('sender_id', '!=', $user->id)])
            ->orderByDesc('last_message_at')
            ->latest();
    }
}
