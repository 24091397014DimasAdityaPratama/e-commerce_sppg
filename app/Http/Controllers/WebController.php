<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\SupplierProfile;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class WebController extends Controller
{
    public function index(Request $request)
    {
        $featuredProducts = Product::with(['category', 'supplier.supplierProfile'])
            ->availableInMarketplace()
            ->where('is_featured', true)
            ->take(6)
            ->get();
        $latestProducts = Product::with(['category', 'supplier.supplierProfile'])
            ->availableInMarketplace()
            ->orderByDesc('created_at')
            ->take(6)
            ->get();
        $categories = Category::where('is_active', true)->get();

        if ($request->user()?->isBuyer()) {
            return view('buyer-home', compact('featuredProducts', 'latestProducts', 'categories'));
        }

        return view('welcome', compact('featuredProducts', 'latestProducts', 'categories'));
    }

    public function catalog(Request $request)
    {
        $products = Product::with(['category', 'supplier.supplierProfile'])
            ->availableInMarketplace()
            ->when($request->category, fn ($query, $value) => $query->where('category_id', $value))
            ->when($request->search, fn ($query, $value) => $query->where('name', 'like', "%{$value}%"))
            ->orderByDesc('is_featured')
            ->paginate(12)
            ->withQueryString();

        $categories = Category::where('is_active', true)->get();

        return view('catalog', compact('products', 'categories'));
    }

    public function product(Product $product)
    {
        $product->load(['category', 'supplier.supplierProfile']);
        abort_unless($product->isAvailableInMarketplace(), 404);

        return view('product', compact('product'));
    }

    public function supplier(User $supplier)
    {
        $supplier->load('supplierProfile');
        abort_unless(
            $supplier->isSupplier()
            && $supplier->status === 'active'
            && $supplier->supplierProfile?->verification_status === 'approved',
            404
        );
        $supplier->setRelation(
            'products',
            $supplier->products()->availableInMarketplace()->with('category')->latest()->get()
        );

        return view('supplier', compact('supplier'));
    }

    public function login(Request $request)
    {
        $captchaQuestion = $this->newCaptchaQuestion($request);

        return view('auth.login', compact('captchaQuestion'));
    }

    public function authenticate(Request $request)
    {
        $captchaAnswer = $request->session()->pull('web_captcha_answer');
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
            'captcha_answer' => $this->captchaAnswerRule($captchaAnswer),
        ]);
        unset($credentials['captcha_answer']);

        if (Auth::attempt([...$credentials, 'status' => 'active'], $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->route(Auth::user()->isBuyer() ? 'home' : 'dashboard');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi salah.',
        ])->with('login_failed', true)->onlyInput('email');
    }

    public function forgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendPasswordResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return back()->withErrors([
                'email' => 'Tunggu sebentar sebelum meminta tautan reset yang baru.',
            ])->onlyInput('email');
        }

        return back()->with('status', 'Jika alamat email terdaftar, tautan reset kata sandi akan dikirim.');
    }

    public function showPasswordResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();
            $user->increment('api_token_version');

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi berhasil diatur ulang. Silakan masuk.');
        }

        return back()->withErrors([
            'email' => 'Tautan reset kata sandi tidak valid atau sudah kedaluwarsa.',
        ])->onlyInput('email');
    }

    public function register(Request $request)
    {
        $captchaQuestion = $this->newCaptchaQuestion($request);

        return view('auth.register', compact('captchaQuestion'));
    }

    public function storeRegistration(Request $request)
    {
        $captchaAnswer = $request->session()->pull('web_captcha_answer');
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users')],
            'contact_person' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'address' => ['required', 'string', 'max:1000'],
            'province' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string', 'max:100'],
            'district' => ['required', 'string', 'max:100'],
            'village' => ['required', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:buyer,supplier'],
            'sppg_id' => ['required_if:role,buyer', 'nullable', 'string', 'max:50', Rule::unique('users', 'sppg_id')],
            'nib_number' => ['required_if:role,supplier', 'nullable', 'digits:13', Rule::unique('supplier_profiles', 'nib_number')],
            'nib_document' => ['required_if:role,supplier', 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'captcha_answer' => $this->captchaAnswerRule($captchaAnswer),
        ]);

        $nibDocumentPath = null;
        if ($data['role'] === 'supplier') {
            $nibDocumentPath = $request->file('nib_document')->store('supplier-nib-documents', 'local');
            if ($nibDocumentPath === false) {
                throw new \RuntimeException('Dokumen NIB gagal disimpan.');
            }
        }

        $user = DB::transaction(function () use ($data, $nibDocumentPath) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'sppg_id' => $data['role'] === 'buyer' ? $data['sppg_id'] : null,
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'status' => 'active',
            ]);

            if ($user->isBuyer()) {
                $user->addresses()->create([
                    'type' => 'work',
                    'full_name' => $data['contact_person'],
                    'phone' => $data['phone'],
                    'province' => $data['province'],
                    'city' => $data['city'],
                    'district' => $data['district'],
                    'village' => $data['village'],
                    'postal_code' => $data['postal_code'] ?? '',
                    'street' => $data['address'],
                    'is_primary' => true,
                ]);
            } else {
                SupplierProfile::create([
                    'user_id' => $user->id,
                    'business_name' => $data['name'],
                    'contact_person' => $data['contact_person'],
                    'province' => $data['province'],
                    'city' => $data['city'],
                    'district' => $data['district'],
                    'village' => $data['village'],
                    'postal_code' => $data['postal_code'] ?? null,
                    'address' => $data['address'],
                    'nib_number' => $data['nib_number'],
                    'nib_document_path' => $nibDocumentPath,
                    'verification_status' => 'pending',
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route($user->isBuyer() ? 'home' : 'dashboard');
    }

    private function newCaptchaQuestion(Request $request): string
    {
        $left = random_int(10, 99);
        $right = random_int(10, 99);
        $request->session()->put('web_captcha_answer', $left + $right);

        return "{$left} + {$right} = ?";
    }

    private function captchaAnswerRule(?int $expected): array
    {
        return [
            'required',
            'integer',
            function (string $attribute, mixed $value, \Closure $fail) use ($expected): void {
                if ($expected === null || (int) $value !== $expected) {
                    $fail('Jawaban CAPTCHA tidak sesuai. Silakan hitung ulang.');
                }
            },
        ];
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function dashboard(Request $request)
    {
        $user = $request->user();
        $summary = [
            'users' => User::count(),
            'products' => Product::count(),
        ];

        if ($user->isSupplier()) {
            $summary['myProducts'] = $user->products()->count();
            $summary['activeProducts'] = $user->products()->where('is_active', true)->count();
            $summary['stockTotal'] = $user->products()->sum('stock');
            $summary['transactions'] = $user->supplierOrders()->count();
            $summary['lowStock'] = $user->products()
                ->where('is_active', true)
                ->whereColumn('stock', '<=', 'low_stock_threshold')
                ->count();
            $summary['pendingOrders'] = $user->supplierOrders()->where('status', 'pending')->count();
            $summary['processingOrders'] = $user->supplierOrders()->where('status', 'processing')->count();
            $summary['sales30Days'] = $user->supplierOrders()
                ->where('payment_status', 'paid')
                ->where('payment_verified_at', '>=', Carbon::now()->subDays(30))
                ->sum('total');
            $products = $user->products()->with('category')->latest()->take(5)->get();
            $lowStockProducts = $user->products()
                ->where('is_active', true)
                ->whereColumn('stock', '<=', 'low_stock_threshold')
                ->orderBy('stock')
                ->take(5)
                ->get();
            $recentOrders = $user->supplierOrders()
                ->with('buyer')
                ->latest()
                ->take(6)
                ->get();

            return view('dashboard', compact('user', 'summary', 'products', 'lowStockProducts', 'recentOrders'));
        }

        if ($user->isBuyer()) {
            $summary['transactions'] = $user->buyerOrders()->count();
            $summary['pendingSuppliers'] = SupplierProfile::where('verification_status', 'pending')->count();
            $products = Product::with('category')->availableInMarketplace()->latest()->take(5)->get();

            return view('dashboard', compact('user', 'summary', 'products'));
        }

        $products = Product::with('category')->availableInMarketplace()->latest()->take(5)->get();

        return view('dashboard', compact('user', 'summary', 'products'));
    }

    public function supplierProductsCreate(Request $request)
    {
        if (! $request->user()?->isSupplier()) {
            abort(403, 'Hanya supplier yang dapat mengakses halaman ini.');
        }

        $categories = Category::where('is_active', true)->get();
        $product = null;

        return view('supplier.products.create', compact('categories', 'product'));
    }

    public function supplierProductsEdit(Request $request, Product $product)
    {
        $this->authorizeSupplierProduct($request, $product);

        $categories = Category::query()
            ->where('is_active', true)
            ->orWhere('id', $product->category_id)
            ->orderBy('name')
            ->get();

        return view('supplier.products.create', compact('categories', 'product'));
    }

    public function supplierProductsStore(Request $request)
    {
        $user = $request->user();

        if (! $user?->isSupplier()) {
            abort(403, 'Hanya supplier yang dapat menambahkan produk.');
        }

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'low_stock_threshold' => ['sometimes', 'integer', 'min:0'],
            'unit' => ['required', 'in:liter,kg,pcs,gram'],
            'unit_quantity' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $imageUrl = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->storePublicly('products', 'public');

            if ($imagePath === false) {
                throw new \RuntimeException('Gambar produk gagal disimpan ke storage.');
            }

            $imageUrl = '/storage/'.$imagePath;
        }

        Product::create([
            'category_id' => $validated['category_id'],
            'supplier_id' => $user->id,
            'name' => $validated['name'],
            'slug' => Product::uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? 'Produk baru dari supplier.',
            'unit' => $validated['unit'],
            'unit_quantity' => $validated['unit_quantity'],
            'price' => $validated['price'],
            'stock' => $validated['stock'],
            'low_stock_threshold' => $validated['low_stock_threshold'] ?? 10,
            'image_url' => $imageUrl,
            'is_featured' => $request->boolean('is_featured', false),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('dashboard')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function supplierProductsUpdate(Request $request, Product $product)
    {
        $this->authorizeSupplierProduct($request, $product);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'low_stock_threshold' => ['required', 'integer', 'min:0'],
            'unit' => ['required', 'in:liter,kg,pcs,gram'],
            'unit_quantity' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_featured' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $oldImageUrl = $product->image_url;
        $imageUrl = $oldImageUrl;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->storePublicly('products', 'public');

            if ($imagePath === false) {
                throw new \RuntimeException('Gambar produk gagal disimpan ke storage.');
            }

            $imageUrl = '/storage/'.$imagePath;
        }

        $slug = $product->slug;
        if ($product->name !== $validated['name']) {
            $slug = Product::uniqueSlug($validated['name'], $product->id);
        }

        $product->update([
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'description' => $validated['description'] ?? null,
            'unit' => $validated['unit'],
            'unit_quantity' => $validated['unit_quantity'],
            'price' => $validated['price'],
            'low_stock_threshold' => $validated['low_stock_threshold'],
            'image_url' => $imageUrl,
            'is_featured' => $request->boolean('is_featured'),
            'is_active' => $request->boolean('is_active'),
        ]);

        if ($imageUrl !== $oldImageUrl) {
            $this->deleteStoredProductImage($oldImageUrl);
        }

        return redirect()->route('dashboard')->with('success', 'Produk berhasil diperbarui.');
    }

    public function supplierProductsDestroy(Request $request, Product $product)
    {
        $this->authorizeSupplierProduct($request, $product);

        if (OrderItem::where('product_id', $product->id)->exists()
            || StockMovement::where('product_id', $product->id)->exists()
            || $product->reviews()->exists()) {
            return back()->with('error', 'Produk memiliki riwayat pesanan, stok, atau ulasan sehingga tidak dapat dihapus. Nonaktifkan produk melalui Edit Produk.');
        }

        $imageUrl = $product->image_url;
        $product->delete();
        $this->deleteStoredProductImage($imageUrl);

        return back()->with('success', 'Produk berhasil dihapus.');
    }

    private function authorizeSupplierProduct(Request $request, Product $product): void
    {
        if (! $request->user()?->isSupplier()) {
            abort(403, 'Hanya supplier yang dapat mengelola produk.');
        }

        abort_unless($product->supplier_id === $request->user()->id, 404);
    }

    private function deleteStoredProductImage(?string $imageUrl): void
    {
        if ($imageUrl && Str::startsWith($imageUrl, '/storage/products/')) {
            Storage::disk('public')->delete(Str::after($imageUrl, '/storage/'));
        }
    }

    public function privacy()
    {
        return view('policies.privacy');
    }

    public function terms()
    {
        return view('policies.terms');
    }
}
