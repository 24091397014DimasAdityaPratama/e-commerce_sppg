<?php

namespace Tests\Feature;

use App\Models\ChatConversation;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ChatPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_buyer_and_supplier_can_exchange_messages_in_a_direct_conversation(): void
    {
        [$buyer, $supplier] = $this->createParticipants();

        $this->actingAs($buyer)
            ->post(route('chat.suppliers.start', $supplier))
            ->assertRedirect();

        $conversation = ChatConversation::firstOrFail();
        $this->assertSame($buyer->id, $conversation->buyer_id);
        $this->assertSame($supplier->id, $conversation->supplier_id);

        $this->actingAs($buyer)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Apakah beras tersedia?'])
            ->assertCreated()
            ->assertJsonPath('message.body', 'Apakah beras tersedia?')
            ->assertJsonPath('message.own', true);

        $this->actingAs($supplier)
            ->getJson(route('chat.messages.index', $conversation).'?after_id=0')
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Apakah beras tersedia?')
            ->assertJsonPath('messages.0.own', false);

        $this->actingAs($supplier)
            ->postJson(route('chat.messages.store', $conversation), ['body' => 'Tersedia, silakan pesan.'])
            ->assertCreated()
            ->assertJsonPath('message.own', true);

        $this->actingAs($buyer)
            ->getJson(route('chat.messages.index', $conversation).'?after_id=1')
            ->assertOk()
            ->assertJsonPath('messages.0.body', 'Tersedia, silakan pesan.')
            ->assertJsonPath('messages.0.own', false);
    }

    public function test_chat_shows_call_attachment_and_emoji_controls(): void
    {
        $buyer = User::factory()->create(['role' => 'buyer']);
        $supplier = User::factory()->create(['role' => 'supplier', 'phone' => '+62 812 3456 7890']);
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($buyer)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee('tel:+6281234567890', false)
            ->assertSee('chat-attachment', false)
            ->assertSee('data-chat-emoji="😊"', false);
    }

    public function test_chat_is_available_from_an_order_for_both_participants(): void
    {
        [$buyer, $supplier, $product] = $this->createParticipantsWithProduct();
        $order = \App\Models\Order::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'subtotal' => 10000,
            'shipping_fee' => 0,
            'discount' => 0,
            'total' => 10000,
        ]);
        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => 10000,
            'subtotal' => 10000,
        ]);

        $this->actingAs($supplier)
            ->post(route('chat.orders.start', $order))
            ->assertRedirect();

        $conversation = ChatConversation::firstOrFail();
        $this->actingAs($buyer)
            ->get(route('chat.show', $conversation))
            ->assertOk()
            ->assertSee($supplier->name);

        $this->actingAs($buyer)
            ->get(route('transactions.show', $order))
            ->assertOk()
            ->assertSee('Chat dengan supplier');
    }

    public function test_users_cannot_read_or_send_messages_in_another_partys_chat(): void
    {
        [$buyer, $supplier] = $this->createParticipants();
        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($otherBuyer)
            ->get(route('chat.show', $conversation))
            ->assertForbidden();

        $this->postJson(route('chat.messages.store', $conversation), ['body' => 'Unauthorized'])
            ->assertForbidden();
    }

    public function test_participants_can_send_and_view_private_image_and_document_attachments(): void
    {
        Storage::fake('local');
        [$buyer, $supplier] = $this->createParticipants();
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);

        $imageResponse = $this->actingAs($buyer)
            ->postJson(route('chat.messages.store', $conversation), [
                'attachment' => UploadedFile::fake()->image('kondisi-produk.jpg'),
            ])
            ->assertCreated()
            ->assertJsonPath('message.attachment_is_image', true);

        $imageId = $imageResponse->json('message.id');
        $imageMessage = $conversation->messages()->findOrFail($imageId);
        Storage::disk('local')->assertExists($imageMessage->attachment_path);

        $this->actingAs($supplier)
            ->get(route('chat.messages.attachment', [$conversation, $imageMessage]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');

        $documentResponse = $this->actingAs($supplier)
            ->postJson(route('chat.messages.store', $conversation), [
                'attachment' => UploadedFile::fake()->create('daftar-harga.pdf', 20, 'application/pdf'),
            ])
            ->assertCreated()
            ->assertJsonPath('message.attachment_is_image', false);

        $documentMessage = $conversation->messages()->findOrFail($documentResponse->json('message.id'));

        $this->actingAs($buyer)
            ->get(route('chat.messages.attachment', [$conversation, $documentMessage]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'attachment; filename=daftar-harga.pdf');

        $otherBuyer = User::factory()->create(['role' => 'buyer']);
        $this->actingAs($otherBuyer)
            ->get(route('chat.messages.attachment', [$conversation, $imageMessage]))
            ->assertForbidden();
    }

    public function test_chat_rejects_unsupported_file_attachments(): void
    {
        [$buyer, $supplier] = $this->createParticipants();
        $conversation = ChatConversation::create([
            'buyer_id' => $buyer->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->actingAs($buyer)
            ->postJson(route('chat.messages.store', $conversation), [
                'attachment' => UploadedFile::fake()->create('program.exe', 10, 'application/octet-stream'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment');
    }

    private function createParticipants(): array
    {
        return [
            User::factory()->create(['role' => 'buyer']),
            User::factory()->create(['role' => 'supplier']),
        ];
    }

    private function createParticipantsWithProduct(): array
    {
        [$buyer, $supplier] = $this->createParticipants();
        $category = Category::create([
            'name' => 'Beras',
            'slug' => 'beras-chat-test',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'name' => 'Beras',
            'slug' => 'beras-chat-test',
            'unit' => 'kg',
            'unit_quantity' => 1,
            'price' => 10000,
            'stock' => 10,
            'is_active' => true,
        ]);

        return [$buyer, $supplier, $product];
    }
}
