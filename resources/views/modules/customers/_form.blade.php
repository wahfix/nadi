@php
    $customer ??= null;
    $method ??= 'POST';
    $submitLabel ??= 'Simpan';
    $employment = $customer?->activeEmployment;
@endphp

<form method="POST" action="{{ $action }}" class="flex flex-col gap-6">
    @csrf
    @method($method)

    <flux:heading size="lg">Data Pribadi</flux:heading>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:field>
            <flux:input
                name="full_name"
                :label="__('Nama Lengkap')"
                :value="old('full_name', $customer?->full_name)"
                required
                :placeholder="__('Nama sesuai KTP')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="national_id_number"
                :label="__('Nomor Induk Kependudukan (NIK)')"
                :value="old('national_id_number', $customer?->national_id_number)"
                required
                :placeholder="__('16 digit angka')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="date_of_birth"
                :label="__('Tanggal Lahir')"
                :value="old('date_of_birth', $customer?->date_of_birth?->format('Y-m-d'))"
                type="date"
                required
            />
        </flux:field>

        <flux:field>
            <flux:select name="gender" :label="__('Jenis Kelamin')" required>
                <flux:select.option value="">-- Pilih --</flux:select.option>
                <flux:select.option value="MALE" :selected="old('gender', $customer?->gender) == 'MALE'">
                    Laki-laki
                </flux:select.option>
                <flux:select.option value="FEMALE" :selected="old('gender', $customer?->gender) == 'FEMALE'">
                    Perempuan
                </flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:input
                name="phone"
                :label="__('Nomor Telepon')"
                :value="old('phone', $customer?->phone)"
                type="tel"
                required
                :placeholder="__('08xxxxxxxxxx')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="email"
                :label="__('Alamat Email')"
                :value="old('email', $customer?->email)"
                type="email"
                :placeholder="__('nama@contoh.test')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="city"
                :label="__('Kota Domisili')"
                :value="old('city', $customer?->city)"
                required
                :placeholder="__('Contoh: Surabaya')"
            />
        </flux:field>

        <flux:field>
            <flux:textarea
                name="address"
                :label="__('Alamat Domisili')"
                :value="old('address', $customer?->address)"
                rows="3"
                required
                :placeholder="__('Alamat lengkap sesuai KTP')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="emergency_contact_name"
                :label="__('Nama Kontak Darurat')"
                :value="old('emergency_contact_name', $customer?->emergency_contact_name)"
                required
                :placeholder="__('Nama keluarga terdekat')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="emergency_contact_phone"
                :label="__('Telepon Kontak Darurat')"
                :value="old('emergency_contact_phone', $customer?->emergency_contact_phone)"
                type="tel"
                required
                :placeholder="__('08xxxxxxxxxx')"
            />
        </flux:field>
    </div>

    <flux:separator />

    <flux:heading size="lg">Pekerjaan</flux:heading>
    <flux:text class="-mt-4">Opsional. Isi apabila nasabah memiliki pekerjaan, diisi untuk penilaian kelayakan pinjaman.</flux:text>

    <div class="grid gap-4 md:grid-cols-2">
        <flux:field>
            <flux:input
                name="employment[company_name]"
                :label="__('Nama Perusahaan')"
                :value="old('employment.company_name', $employment?->company_name)"
                :placeholder="__('Nama tempat bekerja')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="employment[department]"
                :label="__('Divisi')"
                :value="old('employment.department', $employment?->department)"
                :placeholder="__('Contoh: Produksi')"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="employment[position]"
                :label="__('Jabatan')"
                :value="old('employment.position', $employment?->position)"
                :placeholder="__('Contoh: Operator')"
            />
        </flux:field>

        <flux:field>
            <flux:select name="employment[employment_type]" :label="__('Jenis Pekerjaan')">
                <flux:select.option value="">-- Pilih --</flux:select.option>
                <flux:select.option value="PERMANENT" :selected="old('employment.employment_type', $employment?->employment_type) == 'PERMANENT'">
                    Tetap
                </flux:select.option>
                <flux:select.option value="CONTRACT" :selected="old('employment.employment_type', $employment?->employment_type) == 'CONTRACT'">
                    Kontrak
                </flux:select.option>
                <flux:select.option value="SELF_EMPLOYED" :selected="old('employment.employment_type', $employment?->employment_type) == 'SELF_EMPLOYED'">
                    Wiraswasta
                </flux:select.option>
                <flux:select.option value="OTHER" :selected="old('employment.employment_type', $employment?->employment_type) == 'OTHER'">
                    Lainnya
                </flux:select.option>
            </flux:select>
        </flux:field>

        <flux:field>
            <flux:input
                name="employment[employment_start_date]"
                :label="__('Mulai Bekerja')"
                :value="old('employment.employment_start_date', $employment?->employment_start_date?->format('Y-m-d'))"
                type="date"
            />
        </flux:field>

        <flux:field>
            <flux:input
                name="employment[estimated_monthly_income]"
                :label="__('Perkiraan Penghasilan Bulanan')"
                :value="old('employment.estimated_monthly_income', $employment?->estimated_monthly_income !== null ? format_rupiah($employment->estimated_monthly_income, false) : '')"
                inputmode="numeric"
                :placeholder="__('Contoh: 5000000')"
            />
        </flux:field>

        <flux:field>
            <flux:select name="employment[employment_status]" :label="__('Status Pekerjaan')">
                <flux:select.option value="">-- Pilih --</flux:select.option>
                <flux:select.option value="ACTIVE" :selected="old('employment.employment_status', $employment?->employment_status) == 'ACTIVE'">
                    Aktif
                </flux:select.option>
                <flux:select.option value="RESIGNED" :selected="old('employment.employment_status', $employment?->employment_status) == 'RESIGNED'">
                    Mengundurkan Diri
                </flux:select.option>
                <flux:select.option value="TERMINATED" :selected="old('employment.employment_status', $employment?->employment_status) == 'TERMINATED'">
                    Berakhir / Diberhentikan
                </flux:select.option>
                <flux:select.option value="UNKNOWN" :selected="old('employment.employment_status', $employment?->employment_status) == 'UNKNOWN'">
                    Tidak Diketahui
                </flux:select.option>
            </flux:select>
        </flux:field>

        <div class="md:col-span-2">
            <flux:field>
                <flux:textarea
                    name="employment[notes]"
                    :label="__('Catatan')"
                    :value="old('employment.notes', $employment?->notes)"
                    rows="2"
                    :placeholder="__('Catatan tambahan pekerjaan (opsional)')"
                />
            </flux:field>
        </div>
    </div>

    <div class="flex items-center justify-end gap-3 pt-2">
        <flux:button as="a" :href="route('customers.index')" wire:navigate variant="ghost">
            Batal
        </flux:button>
        <flux:button variant="primary" type="submit">
            {{ $submitLabel }}
        </flux:button>
    </div>
</form>