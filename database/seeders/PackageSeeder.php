<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        // Harga contoh lokal; persetujuan harga diperlukan sebelum penggunaan produksi.
        Package::firstOrCreate(['code' => 'rona-once'], ['name' => 'Rona Sekali Bayar', 'price' => 99000, 'is_active' => true]);
    }
}