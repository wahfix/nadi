<x-layouts::auth :title="__('Masuk ke Sistem')">
    <div class="flex flex-col gap-6" x-data="{
        fillAccount(email) {
            document.querySelector('input[name=email]').value = email;
            document.querySelector('input[name=password]').value = 'password';
        }
    }">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Masuk ke Sistem NADI</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Sistem Manajemen Pinjaman & Agunan Internal</p>
        </div>

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <!-- Quick Demo Role Switcher -->
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900/60 p-3 text-xs">
            <p class="font-semibold text-zinc-700 dark:text-zinc-300 mb-2">Pilih Akun Demo Cepat (Password: <span class="font-mono text-emerald-600 dark:text-emerald-400">password</span>):</p>
            <div class="flex flex-wrap gap-1.5">
                <button type="button" @click="fillAccount('admin@example.test')" class="px-2 py-1 rounded bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300 font-medium hover:opacity-80 transition-opacity">Admin</button>
                <button type="button" @click="fillAccount('lo@example.test')" class="px-2 py-1 rounded bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300 font-medium hover:opacity-80 transition-opacity">LO</button>
                <button type="button" @click="fillAccount('lc@example.test')" class="px-2 py-1 rounded bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-medium hover:opacity-80 transition-opacity">LC</button>
                <button type="button" @click="fillAccount('cashier@example.test')" class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-medium hover:opacity-80 transition-opacity">Kasir</button>
                <button type="button" @click="fillAccount('collateral@example.test')" class="px-2 py-1 rounded bg-cyan-100 text-cyan-800 dark:bg-cyan-950 dark:text-cyan-300 font-medium hover:opacity-80 transition-opacity">Agunan</button>
                <button type="button" @click="fillAccount('verifier@example.test')" class="px-2 py-1 rounded bg-indigo-100 text-indigo-800 dark:bg-indigo-950 dark:text-indigo-300 font-medium hover:opacity-80 transition-opacity">Verifikator</button>
                <button type="button" @click="fillAccount('auditor@example.test')" class="px-2 py-1 rounded bg-zinc-200 text-zinc-800 dark:bg-zinc-800 dark:text-zinc-300 font-medium hover:opacity-80 transition-opacity">Auditor</button>
            </div>
        </div>

        <form method="POST" action="{{ route('login.store') }}" class="flex flex-col gap-5">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Alamat Email')"
                :value="request('email', old('email'))"
                type="email"
                required
                autofocus
                autocomplete="email"
                placeholder="nama@example.test"
            />

            <!-- Password -->
            <div class="relative">
                <flux:input
                    name="password"
                    :label="__('Kata Sandi')"
                    :value="request('email') ? 'password' : ''"
                    type="password"
                    required
                    autocomplete="current-password"
                    :placeholder="__('Kata Sandi')"
                    viewable
                />
            </div>

            <!-- Remember Me -->
            <flux:checkbox name="remember" :label="__('Ingat saya di perangkat ini')" :checked="old('remember', true)" />

            <div class="flex items-center justify-end pt-2">
                <flux:button variant="primary" type="submit" class="w-full" data-test="login-button">
                    {{ __('Masuk ke Sistem') }}
                </flux:button>
            </div>
        </form>

        <div class="text-xs text-center text-zinc-500">
            <a href="{{ route('home') }}" class="text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 transition-colors">
                &larr; Kembali ke Halaman Utama
            </a>
        </div>
    </div>
</x-layouts::auth>
