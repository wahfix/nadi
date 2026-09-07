<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NADI — Loan Management System</title>
    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="min-h-screen bg-zinc-900 text-zinc-100 flex flex-col justify-between antialiased">
    <!-- Header -->
    <header class="border-b border-zinc-800 bg-zinc-950/60 backdrop-blur px-6 py-4">
        <div class="max-w-7xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="flex aspect-square size-10 items-center justify-center rounded-lg bg-emerald-600 text-white font-bold text-xl shadow-lg shadow-emerald-950/40">
                    N
                </div>
                <div>
                    <span class="text-xl font-bold tracking-tight text-white">NADI</span>
                    <span class="text-xs block text-zinc-400 font-medium">Loan Management System</span>
                </div>
            </div>

            <nav class="flex items-center gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-colors">
                        Buka Dasbor
                    </a>
                @else
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 transition-colors">
                        Masuk ke Sistem
                    </a>
                @endauth
            </nav>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl mx-auto w-full px-6 py-12 flex flex-col justify-center">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-medium bg-emerald-950 text-emerald-300 border border-emerald-800/60 mb-4">
                <span class="size-2 rounded-full bg-emerald-400 animate-pulse"></span>
                Prototipe Operasional Fungsional End-to-End
            </div>
            <h1 class="text-4xl sm:text-5xl font-extrabold tracking-tight text-white mb-4">
                Sistem Manajemen Pinjaman & Agunan Terintegrasi
            </h1>
            <p class="text-base sm:text-lg text-zinc-400 leading-relaxed">
                Platform internal pengelolaan siklus pinjaman, angsuran bertingkat, penagihan lapangan, dan protokol serah terima agunan terproteksi 8 syarat mutlak.
            </p>
            <div class="mt-8 flex items-center justify-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-lg bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-md hover:bg-emerald-500 transition-colors">
                        Menuju Dasbor Utama &rarr;
                    </a>
                @else
                    <a href="{{ route('login') }}" class="rounded-lg bg-emerald-600 px-6 py-3 text-base font-semibold text-white shadow-md hover:bg-emerald-500 transition-colors">
                        Masuk Menggunakan Akun Demo &rarr;
                    </a>
                @endauth
            </div>
        </div>

        <!-- Demo Accounts Grid -->
        <div class="bg-zinc-950/80 border border-zinc-800 rounded-2xl p-6 sm:p-8 shadow-xl">
            <div class="flex items-center justify-between border-b border-zinc-800 pb-4 mb-6">
                <div>
                    <h2 class="text-lg font-bold text-white">Akun Pengguna Demo Sintetis (7 Peran RBAC)</h2>
                    <p class="text-xs text-zinc-400 mt-0.5">Password dev untuk seluruh akun demo: <span class="font-mono text-emerald-400 font-semibold bg-zinc-900 px-2 py-0.5 rounded">password</span></p>
                </div>
                <span class="text-xs px-2.5 py-1 rounded bg-zinc-800 text-zinc-300 font-mono">SQLite Seeder</span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                <!-- 1. ADMIN -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-purple-950 text-purple-300 border border-purple-800">ADMIN</span>
                            <span class="text-[11px] text-zinc-400">Akses Penuh</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Administrator NADI</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">admin@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Persetujuan pinjaman, pencairan, audit, pengaturan.</p>
                    </div>
                    <a href="{{ route('login') }}?email=admin@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 2. LO -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-blue-950 text-blue-300 border border-blue-800">LO</span>
                            <span class="text-[11px] text-zinc-400">Loan Officer</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Loan Officer Budi</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">lo@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Input data nasabah & pembuatan draft pengajuan pinjaman.</p>
                    </div>
                    <a href="{{ route('login') }}?email=lo@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 3. LC -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-amber-950 text-amber-300 border border-amber-800">LC</span>
                            <span class="text-[11px] text-zinc-400">Loan Collector</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Loan Collector Agus</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">lc@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Dasbor penagihan, pantau tunggakan & janji bayar.</p>
                    </div>
                    <a href="{{ route('login') }}?email=lc@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 4. CASHIER -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-emerald-950 text-emerald-300 border border-emerald-800">CASHIER</span>
                            <span class="text-[11px] text-zinc-400">Kasir</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Kasir Siti</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">cashier@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Terima setoran angsuran & cetak kuitansi pembayaran.</p>
                    </div>
                    <a href="{{ route('login') }}?email=cashier@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 5. COLLATERAL_OFFICER -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-cyan-950 text-cyan-300 border border-cyan-800">COLLATERAL</span>
                            <span class="text-[11px] text-zinc-400">Petugas Agunan</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Petugas Agunan Hendra</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">collateral@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Penerimaan fisik agunan & eksekusi serah terima 8 syarat.</p>
                    </div>
                    <a href="{{ route('login') }}?email=collateral@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 6. IDENTITY_VERIFIER -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-indigo-950 text-indigo-300 border border-indigo-800">VERIFIER</span>
                            <span class="text-[11px] text-zinc-400">Verifikator</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Petugas Verifikasi Rina</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">verifier@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Pemeriksaan dan pencocokan keabsahan identitas pemohon.</p>
                    </div>
                    <a href="{{ route('login') }}?email=verifier@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>

                <!-- 7. AUDITOR -->
                <div class="p-4 rounded-xl border border-zinc-800 bg-zinc-900/60 hover:border-emerald-700/50 transition-colors flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-semibold px-2 py-0.5 rounded bg-zinc-800 text-zinc-300 border border-zinc-700">AUDITOR</span>
                            <span class="text-[11px] text-zinc-400">Read-Only</span>
                        </div>
                        <h3 class="font-semibold text-sm text-zinc-100">Auditor Dewi</h3>
                        <p class="text-xs font-mono text-zinc-400 mt-1 select-all">auditor@example.test</p>
                        <p class="text-[11px] text-zinc-400 mt-2">Akses baca riwayat jejak audit, laporan finansial lengkap.</p>
                    </div>
                    <a href="{{ route('login') }}?email=auditor@example.test" class="mt-4 inline-flex items-center justify-center text-xs font-medium text-emerald-400 hover:text-emerald-300 pt-2 border-t border-zinc-800">
                        Pilih Akun Ini &rarr;
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="border-t border-zinc-800 bg-zinc-950/40 px-6 py-4 text-center text-xs text-zinc-500">
        <p>&copy; {{ date('Y') }} NADI — Loan Management System. Hak Cipta Dilindungi.</p>
        <p class="mt-1 text-[11px] text-zinc-600">Prototipe internal untuk keperluan evaluasi fungsional dan pengujian operasional.</p>
    </footer>

    @fluxScripts
</body>
</html>
