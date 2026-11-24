<div>
    @if ($turnstileSiteKey)
        <p class="mb-2 text-sm font-semibold text-slate-700">Verifikasi CAPTCHA</p>
        <div class="cf-turnstile" data-sitekey="{{ $turnstileSiteKey }}"></div>
        @error('cf-turnstile-response')
            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
        @enderror
        @once
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endonce
    @else
        <label for="captcha_answer" class="mb-1.5 block text-sm font-semibold text-slate-700">Kode CAPTCHA: {{ $captchaQuestion }}</label>
        <input id="captcha_answer" name="captcha_answer" type="number" inputmode="numeric" min="20" max="198" autocomplete="off" required aria-invalid="{{ $errors->has('captcha_answer') ? 'true' : 'false' }}" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4">
        @error('captcha_answer')
            <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
        @enderror
    @endif
</div>
