@extends('layouts.app')

@section('title', 'Daftar - SPPG MART')

@section('content')
<div class="mx-auto max-w-lg px-4 py-8 sm:py-12">
    <div class="rounded-2xl border border-sky-100 bg-white p-5 shadow-sm sm:rounded-3xl sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[0.18em] text-[#167ba5]">Registrasi</p>
        <h1 class="mt-2 text-2xl font-black text-slate-900 sm:text-3xl">Daftar akun baru</h1>
        <p class="mt-2 text-sm text-slate-600">Buat akun untuk mulai menggunakan SPPG MART.</p>

        <form
            method="POST"
            action="{{ route('register') }}"
            enctype="multipart/form-data"
            data-registration-form
            data-region-base="{{ url('/regions') }}"
            data-old-province="{{ old('province') }}"
            data-old-city="{{ old('city') }}"
            data-old-district="{{ old('district') }}"
            data-old-village="{{ old('village') }}"
            data-initial-step="{{ $errors->hasAny(['name', 'role', 'sppg_id', 'nib_number', 'nib_document']) ? 1 : ($errors->hasAny(['contact_person', 'phone', 'email']) ? 2 : ($errors->hasAny(['address', 'postal_code']) ? 3 : ($errors->hasAny(['province', 'city', 'district', 'village']) ? 4 : ($errors->hasAny(['password', 'password_confirmation', 'captcha_answer', 'cf-turnstile-response']) ? 5 : 1)))) }}"
            novalidate
            class="mt-5 space-y-4 sm:mt-6"
        >
            @csrf
            <div>
                <p id="registration-step-caption" class="text-sm font-bold text-[#126586]" aria-live="polite"></p>
                <div class="mt-2 grid grid-cols-5 gap-1.5" aria-hidden="true">
                    @foreach (range(1, 5) as $step)
                        <span data-step-progress class="h-1.5 rounded-full bg-slate-200 transition-colors"></span>
                    @endforeach
                </div>
            </div>

            <section data-registration-step="1" class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div class="sm:col-span-2">
                    <label for="name" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Perusahaan / Nama Dapur</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="organization" maxlength="255" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}">
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="mb-1.5 text-sm font-semibold text-slate-700">Daftar sebagai</legend>
                    <div class="grid grid-cols-2 gap-2 sm:gap-3">
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="buyer" @checked(old('role', 'buyer') === 'buyer') required class="peer sr-only">
                            <span class="flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 transition hover:border-sky-300 peer-checked:border-[#167ba5] peer-checked:bg-sky-50 peer-checked:text-[#126586] peer-focus-visible:ring-2 peer-focus-visible:ring-sky-300">SPPG</span>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="supplier" @checked(old('role') === 'supplier') required class="peer sr-only">
                            <span class="flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-3 text-sm font-semibold text-slate-600 transition hover:border-sky-300 peer-checked:border-[#167ba5] peer-checked:bg-sky-50 peer-checked:text-[#126586] peer-focus-visible:ring-2 peer-focus-visible:ring-sky-300">Pemasok</span>
                        </label>
                    </div>
                    @error('role')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </fieldset>
                <div id="buyer-registration-fields" class="sm:col-span-2">
                    <label for="sppg_id" class="mb-1.5 block text-sm font-semibold text-slate-700">ID SPPG</label>
                    <input id="sppg_id" name="sppg_id" type="text" value="{{ old('sppg_id') }}" maxlength="50" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('sppg_id') ? 'true' : 'false' }}">
                    @error('sppg_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div id="supplier-registration-fields" class="hidden sm:col-span-2">
                    <div class="grid gap-3 sm:grid-cols-2 sm:gap-4">
                        <div>
                            <label for="nib_number" class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor Induk Berusaha (NIB)</label>
                            <input id="nib_number" name="nib_number" type="text" value="{{ old('nib_number') }}" inputmode="numeric" pattern="[0-9]{13}" maxlength="13" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('nib_number') ? 'true' : 'false' }}" disabled>
                            @error('nib_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="nib_document" class="mb-1.5 block text-sm font-semibold text-slate-700">Upload Dokumen NIB</label>
                            <input id="nib_document" name="nib_document" type="file" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs text-slate-700 file:mr-2 file:rounded-lg file:border-0 file:bg-sky-50 file:px-3 file:py-2 file:font-semibold file:text-[#126586]" required aria-invalid="{{ $errors->has('nib_document') ? 'true' : 'false' }}" disabled>
                            <p class="mt-1 text-xs text-slate-500">PDF/JPG/PNG, maksimal 10 MB.</p>
                            @error('nib_document')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>
            </section>

            <section data-registration-step="2" class="hidden grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div>
                    <label for="contact_person" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Penanggung Jawab</label>
                    <input id="contact_person" name="contact_person" type="text" value="{{ old('contact_person') }}" autocomplete="name" maxlength="255" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('contact_person') ? 'true' : 'false' }}">
                    @error('contact_person')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">Nomor WhatsApp</label>
                    <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel" inputmode="tel" maxlength="30" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('phone') ? 'true' : 'false' }}">
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-700">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" inputmode="email" maxlength="255" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}">
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            <section data-registration-step="3" class="hidden grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div class="sm:col-span-2">
                    <label for="address" class="mb-1.5 block text-sm font-semibold text-slate-700">Alamat Lengkap</label>
                    <textarea id="address" name="address" rows="3" maxlength="1000" autocomplete="street-address" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('address') ? 'true' : 'false' }}">{{ old('address') }}</textarea>
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="postal_code" class="mb-1.5 block text-sm font-semibold text-slate-700">Kode Pos <span class="font-normal text-slate-400">(opsional)</span></label>
                    <input id="postal_code" name="postal_code" type="text" value="{{ old('postal_code') }}" autocomplete="postal-code" maxlength="20" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4">
                    @error('postal_code')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </section>

            <section data-registration-step="4" class="hidden grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div>
                    <label for="province" class="mb-1.5 block text-sm font-semibold text-slate-700">Nama Provinsi</label>
                    <select id="province" name="province" required disabled class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Memuat provinsi...</option>
                    </select>
                    @error('province')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="city" class="mb-1.5 block text-sm font-semibold text-slate-700">Kabupaten/Kota</label>
                    <select id="city" name="city" required disabled class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Pilih provinsi dahulu</option>
                    </select>
                    @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="district" class="mb-1.5 block text-sm font-semibold text-slate-700">Kecamatan</label>
                    <select id="district" name="district" required disabled class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Pilih kabupaten/kota dahulu</option>
                    </select>
                    @error('district')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="village" class="mb-1.5 block text-sm font-semibold text-slate-700">Desa/Kelurahan</label>
                    <select id="village" name="village" required disabled class="min-h-11 w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100">
                        <option value="">Pilih kecamatan dahulu</option>
                    </select>
                    @error('village')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div id="region-status" class="text-xs text-slate-500 sm:col-span-2" role="status" aria-live="polite">Memuat daftar wilayah Indonesia...</div>
                <button id="region-retry" type="button" class="hidden rounded-lg border border-sky-200 px-3 py-2 text-xs font-semibold text-[#126586] hover:bg-sky-50 sm:col-span-2">Coba muat ulang daftar wilayah</button>
            </section>

            <section data-registration-step="5" class="hidden grid gap-3 sm:grid-cols-2 sm:gap-4">
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">Kata sandi</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}">
                    @error('password')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-semibold text-slate-700">Konfirmasi kata sandi</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" class="min-h-11 w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-sky-500 focus:outline-none focus:ring-2 focus:ring-sky-100 sm:px-4" required>
                    @error('password_confirmation')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="sm:col-span-2">
                    @include('auth.partials.captcha')
                </div>
            </section>

            <div class="flex items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <button id="registration-back" type="button" class="hidden min-h-11 rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50">Kembali</button>
                <span id="registration-step-spacer" class="flex-1"></span>
                <button id="registration-next" type="button" class="min-h-11 rounded-xl bg-[#167ba5] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#105f82]">Lanjut</button>
                <button id="registration-submit" type="submit" class="hidden min-h-11 rounded-xl bg-[#167ba5] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#105f82]">Daftar</button>
            </div>
        </form>

        <p class="mt-5 text-center text-sm text-slate-500">Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-[#126586] hover:underline">Masuk</a></p>
    </div>
</div>

<script>
    (() => {
        const form = document.querySelector('[data-registration-form]');
        if (!form) return;

        const province = document.getElementById('province');
        const city = document.getElementById('city');
        const district = document.getElementById('district');
        const village = document.getElementById('village');
        const status = document.getElementById('region-status');
        const retry = document.getElementById('region-retry');
        const buyerFields = document.getElementById('buyer-registration-fields');
        const supplierFields = document.getElementById('supplier-registration-fields');
        const steps = [...form.querySelectorAll('[data-registration-step]')];
        const stepCaption = document.getElementById('registration-step-caption');
        const progressBars = [...form.querySelectorAll('[data-step-progress]')];
        const backButton = document.getElementById('registration-back');
        const nextButton = document.getElementById('registration-next');
        const submitButton = document.getElementById('registration-submit');
        const stepNames = ['Identitas', 'Kontak', 'Alamat', 'Wilayah', 'Keamanan'];
        const regionCache = new Map();
        let requestVersion = 0;
        let lastSelection = {};
        let currentStep = Number(form.dataset.initialStep);

        const resetSelect = (select, placeholder) => {
            select.replaceChildren(new Option(placeholder, ''));
            select.disabled = true;
        };

        const getAreas = async (path) => {
            if (regionCache.has(path)) return regionCache.get(path);

            const response = await fetch(`${form.dataset.regionBase}/${path}`, {
                headers: { Accept: 'application/json' },
            });
            if (!response.ok) throw new Error('Daftar wilayah tidak dapat dimuat.');

            const result = await response.json();
            if (!Array.isArray(result.data)) throw new Error('Format data wilayah tidak dikenali.');

            regionCache.set(path, result.data);
            return result.data;
        };

        const fillSelect = (select, areas, placeholder, selectedName) => {
            select.replaceChildren(new Option(placeholder, ''));
            areas.forEach((area) => {
                const option = new Option(area.name, area.name);
                option.dataset.code = area.code;
                select.add(option);
            });
            select.disabled = false;
            if (selectedName) select.value = selectedName;
            return select.selectedOptions[0]?.dataset.code || null;
        };

        const toggleRoleFields = () => {
            const isSupplier = form.querySelector('input[name="role"]:checked')?.value === 'supplier';
            buyerFields.classList.toggle('hidden', isSupplier);
            supplierFields.classList.toggle('hidden', !isSupplier);
            buyerFields.querySelectorAll('input').forEach((input) => {
                input.disabled = isSupplier;
                input.required = !isSupplier;
            });
            supplierFields.querySelectorAll('input').forEach((input) => {
                input.disabled = !isSupplier;
                input.required = isSupplier;
            });
        };

        const renderStep = (scrollToForm = false) => {
            steps.forEach((step, index) => {
                step.classList.toggle('hidden', index + 1 !== currentStep);
            });
            stepCaption.textContent = `Langkah ${currentStep} dari ${steps.length}: ${stepNames[currentStep - 1]}`;
            progressBars.forEach((bar, index) => {
                bar.classList.toggle('bg-[#167ba5]', index < currentStep);
                bar.classList.toggle('bg-slate-200', index >= currentStep);
            });
            backButton.classList.toggle('hidden', currentStep === 1);
            nextButton.classList.toggle('hidden', currentStep === steps.length);
            submitButton.classList.toggle('hidden', currentStep !== steps.length);
            document.getElementById('registration-step-spacer').classList.toggle('flex-1', currentStep === 1);

            if (scrollToForm) {
                form.closest('.rounded-2xl')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        };

        const validateCurrentStep = () => {
            const activeStep = steps[currentStep - 1];
            const fields = [...activeStep.querySelectorAll('input, select, textarea')].filter((field) => !field.disabled);

            for (const field of fields) {
                if (!field.checkValidity()) {
                    field.reportValidity();
                    return false;
                }
            }

            if (currentStep === 4 && [province, city, district, village].some((select) => select.disabled)) {
                if (retry.classList.contains('hidden')) {
                    status.textContent = 'Daftar wilayah belum siap. Tunggu pemuatan selesai, lalu coba lagi.';
                }
                return false;
            }

            return true;
        };

        nextButton.addEventListener('click', () => {
            if (!validateCurrentStep()) return;
            currentStep += 1;
            renderStep(true);
        });
        backButton.addEventListener('click', () => {
            currentStep -= 1;
            renderStep(true);
        });
        form.addEventListener('submit', (event) => {
            if (!validateCurrentStep()) {
                event.preventDefault();
                return;
            }
            if (currentStep !== steps.length) {
                event.preventDefault();
                currentStep += 1;
                renderStep(true);
            }
        });

        const loadRegions = async (selection = {}) => {
            lastSelection = selection;
            const version = ++requestVersion;
            status.textContent = 'Memuat daftar wilayah Indonesia...';
            retry.classList.add('hidden');
            resetSelect(province, 'Pilih provinsi');
            resetSelect(city, 'Pilih provinsi dahulu');
            resetSelect(district, 'Pilih kabupaten/kota dahulu');
            resetSelect(village, 'Pilih kecamatan dahulu');

            try {
                const provinces = await getAreas('provinces');
                if (version !== requestVersion) return;
                const provinceCode = fillSelect(province, provinces, 'Pilih provinsi', selection.province);
                if (!provinceCode) {
                    status.textContent = 'Pilih provinsi untuk memuat kabupaten/kota, kecamatan, dan desa.';
                    return;
                }

                const cities = await getAreas(`regencies/${provinceCode}`);
                if (version !== requestVersion) return;
                const cityCode = fillSelect(city, cities, 'Pilih kabupaten/kota', selection.city);
                if (!cityCode) {
                    status.textContent = '';
                    return;
                }

                const districts = await getAreas(`districts/${cityCode}`);
                if (version !== requestVersion) return;
                const districtCode = fillSelect(district, districts, 'Pilih kecamatan', selection.district);
                if (!districtCode) {
                    status.textContent = '';
                    return;
                }

                const villages = await getAreas(`villages/${districtCode}`);
                if (version !== requestVersion) return;
                fillSelect(village, villages, 'Pilih desa/kelurahan', selection.village);
                status.textContent = '';
            } catch (error) {
                if (version !== requestVersion) return;
                resetSelect(province, 'Wilayah gagal dimuat');
                resetSelect(city, 'Pilih provinsi dahulu');
                resetSelect(district, 'Pilih kabupaten/kota dahulu');
                resetSelect(village, 'Pilih kecamatan dahulu');
                status.textContent = `${error.message} Periksa koneksi internet, lalu coba lagi.`;
                retry.classList.remove('hidden');
            }
        };

        province.addEventListener('change', () => loadRegions({ province: province.value }));
        city.addEventListener('change', () => loadRegions({ province: province.value, city: city.value }));
        district.addEventListener('change', () => loadRegions({
            province: province.value,
            city: city.value,
            district: district.value,
        }));
        village.addEventListener('change', () => loadRegions({
            province: province.value,
            city: city.value,
            district: district.value,
            village: village.value,
        }));
        retry.addEventListener('click', () => loadRegions(lastSelection));
        form.querySelectorAll('input[name="role"]').forEach((input) => input.addEventListener('change', toggleRoleFields));

        toggleRoleFields();
        renderStep();
        loadRegions({
            province: form.dataset.oldProvince,
            city: form.dataset.oldCity,
            district: form.dataset.oldDistrict,
            village: form.dataset.oldVillage,
        });
    })();
</script>
@endsection
