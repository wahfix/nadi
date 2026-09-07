<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Services\SequentialNumberService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed realistic synthetic customer profiles (no real personal data).
     */
    public function run(SequentialNumberService $sequentialNumberService): void
    {
        $rows = [
            ['full_name' => 'Budi Santoso', 'gender' => 'MALE', 'city' => 'Surabaya', 'company' => 'PT Nusantara Sejahtera', 'position' => 'Operator Produksi', 'type' => 'PERMANENT', 'income' => 4500000],
            ['full_name' => 'Siti Rahayu', 'gender' => 'FEMALE', 'city' => 'Surabaya', 'company' => 'PDAM Surya Tirta', 'position' => 'Staf Administrasi', 'type' => 'PERMANENT', 'income' => 4200000],
            ['full_name' => 'Agus Setiawan', 'gender' => 'MALE', 'city' => 'Bandung', 'company' => 'PT Karya Cipta Bangun', 'position' => 'Mandor', 'type' => 'CONTRACT', 'income' => 5500000],
            ['full_name' => 'Dewi Lestari', 'gender' => 'FEMALE', 'city' => 'Jakarta Selatan', 'company' => 'PT Mitra Retailindo', 'position' => 'Supervisor Toko', 'type' => 'PERMANENT', 'income' => 6500000],
            ['full_name' => 'Eko Prasetyo', 'gender' => 'MALE', 'city' => 'Semarang', 'company' => null, 'position' => null, 'type' => 'SELF_EMPLOYED', 'income' => 7000000],
            ['full_name' => 'Fitri Handayani', 'gender' => 'FEMALE', 'city' => 'Medan', 'company' => 'PT Deli Transport', 'position' => 'Staf Logistik', 'type' => 'CONTRACT', 'income' => 4800000],
            ['full_name' => 'Gunawan Wibowo', 'gender' => 'MALE', 'city' => 'Jakarta Timur', 'company' => 'PT Graha Perkasa', 'position' => 'Teknisi Mesin', 'type' => 'PERMANENT', 'income' => 6000000],
            ['full_name' => 'Hendra Gunawan', 'gender' => 'MALE', 'city' => 'Tangerang', 'company' => null, 'position' => null, 'type' => 'OTHER', 'income' => 3800000],
            ['full_name' => 'Intan Permatasari', 'gender' => 'FEMALE', 'city' => 'Yogyakarta', 'company' => 'PT Batik Adiwarna', 'position' => 'Desainer', 'type' => 'PERMANENT', 'income' => 5200000],
            ['full_name' => 'Joko Susanto', 'gender' => 'MALE', 'city' => 'Surabaya', 'company' => 'CV Jaya Abadi', 'position' => 'Kepala Gudang', 'type' => 'PERMANENT', 'income' => 5000000],
            ['full_name' => 'Kartika Sari', 'gender' => 'FEMALE', 'city' => 'Bandung', 'company' => 'PT Telaga Hijau', 'position' => 'Akuntan', 'type' => 'PERMANENT', 'income' => 7200000],
            ['full_name' => 'Lukman Hakim', 'gender' => 'MALE', 'city' => 'Makassar', 'company' => 'PT Bahari Samudera', 'position' => 'Awak Kapal', 'type' => 'CONTRACT', 'income' => 6800000],
            ['full_name' => 'Maria Ulfa', 'gender' => 'FEMALE', 'city' => 'Depok', 'company' => 'PT Bangun Persada', 'position' => 'Staf HRD', 'type' => 'PERMANENT', 'income' => 5800000],
            ['full_name' => 'Nur Aini', 'gender' => 'FEMALE', 'city' => 'Bekasi', 'company' => null, 'position' => null, 'type' => 'SELF_EMPLOYED', 'income' => 4100000],
            ['full_name' => 'Oki Firmansyah', 'gender' => 'MALE', 'city' => 'Palembang', 'company' => 'PT Sriwijaya Logistik', 'position' => 'Supir Angkut', 'type' => 'CONTRACT', 'income' => 4700000],
            ['full_name' => 'Putri Aisyah', 'gender' => 'FEMALE', 'city' => 'Surabaya', 'company' => 'RS Camar Sehat', 'position' => 'Perawat', 'type' => 'PERMANENT', 'income' => 6100000],
            ['full_name' => 'Rizky Ramadhan', 'gender' => 'MALE', 'city' => 'Jakarta Barat', 'company' => 'PT Digital Nusantara', 'position' => 'Programmer', 'type' => 'PERMANENT', 'income' => 9500000],
            ['full_name' => 'Sri Wahyuni', 'gender' => 'FEMALE', 'city' => 'Bogor', 'company' => 'CV Agro Subur', 'position' => 'Administrasi Pemasaran', 'type' => 'PERMANENT', 'income' => 4300000],
            ['full_name' => 'Toni Kurniawan', 'gender' => 'MALE', 'city' => 'Denpasar', 'company' => 'PT Wisata Nusa', 'position' => 'Pemandu Wisata', 'type' => 'CONTRACT', 'income' => 4900000],
            ['full_name' => 'Yuni Hartati', 'gender' => 'FEMALE', 'city' => 'Malang', 'company' => 'PT Apel Sejahtera', 'position' => 'Staf Keuangan', 'type' => 'PERMANENT', 'income' => 4600000],
        ];

        foreach ($rows as $index => $row) {
            $customer = Customer::create([
                'customer_code' => $sequentialNumberService->generateCustomerCode(),
                'full_name' => $row['full_name'],
                'national_id_number' => '35'.str_pad((string) (150000000000 + ($index + 1) * 424242), 14, '0', STR_PAD_LEFT),
                'date_of_birth' => CarbonImmutable::parse("19{$index}80-0".(($index % 9) + 1).'-'.str_pad((string) (($index % 27) + 1), 2, '0', STR_PAD_LEFT)),
                'gender' => $row['gender'],
                'phone' => '08'.str_pad((string) (8000000000 + $index * 71113), 9, '0', STR_PAD_LEFT),
                'email' => 'nasabah'.($index + 1).'@sintetik.test',
                'address' => 'Jl. Melati No. '.($index + 1).', RT 00'.(($index % 9) + 1).'/0'.(($index % 5) + 1),
                'city' => $row['city'],
                'emergency_contact_name' => 'Keluarga Nasabah '.($index + 1),
                'emergency_contact_phone' => '08'.str_pad((string) (8210000000 + $index * 35771), 9, '0', STR_PAD_LEFT),
                'status' => $index % 15 === 0 ? Customer::STATUS_INACTIVE : Customer::STATUS_ACTIVE,
            ]);

            if ($row['company'] !== null) {
                $customer->employments()->create([
                    'company_name' => $row['company'],
                    'department' => null,
                    'position' => $row['position'],
                    'employment_type' => $row['type'],
                    'employment_start_date' => CarbonImmutable::parse('2021-01-01'),
                    'estimated_monthly_income' => $row['income'],
                    'employment_status' => 'ACTIVE',
                    'notes' => null,
                ]);
            }
        }
    }
}
