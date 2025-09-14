<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Tenant;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\PelayananJenis; // <-- PERBAIKAN: Baris ini ditambahkan
use App\Models\PelayananRequest;
use App\Models\PelayananAttachment;

class InitialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run() // Mengubah return type menjadi void agar sesuai standar modern
    {
        // === Tenants (Nagari) ===
        $nagari1 = Tenant::firstOrCreate(
            ['subdomain' => 'cilandak'],
            ['name' => 'Nagari Cilandak', 'type' => 'subdomain']
        );

        $nagari2 = Tenant::firstOrCreate(
            ['subdomain' => 'kemang'],
            ['name' => 'Nagari Kemang', 'type' => 'subdomain']
        );

        // === Roles ===
        $adminRole = Role::firstOrCreate(
            ['name' => 'Admin Nagari'],
            ['description' => 'Mengelola seluruh data nagari']
        );

        $operatorRole = Role::firstOrCreate(
            ['name' => 'Operator'],
            ['description' => 'Menginput data warga']
        );

        // === Sub Roles untuk Operator ===
        $subPenduduk = SubRole::firstOrCreate(
            ['role_id' => $operatorRole->id, 'name' => 'Penduduk Manager'],
            ['description' => 'Mengelola data penduduk']
        );

        $subSurat = SubRole::firstOrCreate(
            ['role_id' => $operatorRole->id, 'name' => 'Surat Manager'],
            ['description' => 'Mengelola data surat']
        );

        // === Global Admin ===
        $globalAdmin = User::firstOrCreate(
            ['email' => 'admin@pusat.com'],
            ['name' => 'Super Admin', 'password' => Hash::make('password'), 'is_global' => true]
        );

        // === Staff Nagari 1 ===
        $staff1 = User::firstOrCreate(
            ['email' => 'admin.cilandak@app.com'],
            ['name' => 'Admin Cilandak', 'password' => Hash::make('password'), 'is_global' => false]
        );

        $staff2 = User::firstOrCreate(
            ['email' => 'operator.cilandak@app.com'],
            ['name' => 'Operator Cilandak', 'password' => Hash::make('password'), 'is_global' => false]
        );

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $staff1->id, 'role_id' => $adminRole->id, 'tenant_id' => $nagari1->id],
            []
        );

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $staff2->id, 'role_id' => $operatorRole->id, 'tenant_id' => $nagari1->id],
            []
        );

        DB::table('sub_role_user')->updateOrInsert(
            ['user_id' => $staff2->id, 'sub_role_id' => $subPenduduk->id, 'tenant_id' => $nagari1->id],
            []
        );

        DB::table('sub_role_user')->updateOrInsert(
            ['user_id' => $staff2->id, 'sub_role_id' => $subSurat->id, 'tenant_id' => $nagari1->id],
            []
        );

        // === Staff Nagari 2 ===
        $staff3 = User::firstOrCreate(
            ['email' => 'admin.kemang@app.com'],
            ['name' => 'Admin Kemang', 'password' => Hash::make('password'), 'is_global' => false]
        );

        $staff4 = User::firstOrCreate(
            ['email' => 'operator.kemang@app.com'],
            ['name' => 'Operator Kemang', 'password' => Hash::make('password'), 'is_global' => false]
        );

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $staff3->id, 'role_id' => $adminRole->id, 'tenant_id' => $nagari2->id],
            []
        );

        DB::table('role_user')->updateOrInsert(
            ['user_id' => $staff4->id, 'role_id' => $operatorRole->id, 'tenant_id' => $nagari2->id],
            []
        );

        DB::table('sub_role_user')->updateOrInsert(
            ['user_id' => $staff4->id, 'sub_role_id' => $subPenduduk->id, 'tenant_id' => $nagari2->id],
            []
        );

        DB::table('sub_role_user')->updateOrInsert(
            ['user_id' => $staff4->id, 'sub_role_id' => $subSurat->id, 'tenant_id' => $nagari2->id],
            []
        );

        // === Seeder untuk Jenis Pelayanan ===
        // Lebih baik memisahkan ini ke PelayananJenisSeeder.php,
        // namun jika ingin digabung, ini akan berfungsi.
        DB::table('pelayanan_jenis')->truncate();

        // $jenisSurat = [
        //     // A. Pelayanan Administrasi Kependudukan
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Domisili', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Pindah Datang', 'syarat' => json_encode(['Surat Pindah dari daerah asal', 'Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Pindah Keluar', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Kelahiran', 'syarat' => json_encode(['Surat Keterangan dari Bidan/Dokter', 'Fotokopi KK', 'Fotokopi KTP Orang Tua'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Kematian', 'syarat' => json_encode(['Surat Keterangan dari Rumah Sakit/Dokter', 'Fotokopi KTP Almarhum/ah', 'Fotokopi KTP Pelapor'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Belum Menikah', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK', 'Surat Pengantar RT/RW'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Nikah (untuk ke KUA)', 'syarat' => json_encode(['Fotokopi KTP Calon Suami & Istri', 'Fotokopi KK', 'Pas Foto'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Janda/Duda', 'syarat' => json_encode(['Fotokopi KTP', 'Akta Cerai/Surat Kematian Pasangan'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Penghasilan', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Tidak Mampu', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Catatan Kepolisian (SKCK)', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK', 'Pas Foto'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Usaha', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Izin Keramaian', 'syarat' => json_encode(['Fotokopi KTP Penanggung Jawab'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Kepemilikan Tanah', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi Sertifikat/AJB'])],
        //     ['kategori' => 'Kependudukan', 'nama' => 'Surat Keterangan Ahli Waris', 'syarat' => json_encode(['Fotokopi KTP Ahli Waris', 'Fotokopi KK', 'Surat Kematian'])],

        //     // B. Pelayanan Administrasi Perizinan
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Mendirikan Bangunan (IMB)', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi Sertifikat Tanah', 'Gambar Rencana Bangunan'])],
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Tempat Usaha (SITU)', 'syarat' => json_encode(['Fotokopi KTP', 'Akta Pendirian Usaha'])],
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Gangguan (HO)', 'syarat' => json_encode(['Fotokopi KTP', 'Surat Persetujuan Tetangga'])],
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Usaha Perdagangan (SIUP)', 'syarat' => json_encode(['Fotokopi KTP', 'NPWP'])],
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Trayek', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi STNK'])],
        //     ['kategori' => 'Perizinan', 'nama' => 'Surat Izin Reklame', 'syarat' => json_encode(['Fotokopi KTP', 'Desain Reklame'])],

        //     // C. Pelayanan Administrasi Sosial
        //     ['kategori' => 'Sosial', 'nama' => 'Surat Keterangan Miskin', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Sosial', 'nama' => 'Surat Keterangan untuk Beasiswa', 'syarat' => json_encode(['Fotokopi KTP', 'Kartu Pelajar/Mahasiswa'])],
        //     ['kategori' => 'Sosial', 'nama' => 'Surat Keterangan untuk Bantuan Sosial', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],
        //     ['kategori' => 'Sosial', 'nama' => 'Surat Rekomendasi Jamkesmas/Jamkesda', 'syarat' => json_encode(['Fotokopi KTP', 'Fotokopi KK'])],

        //     // D. Pelayanan Administrasi Pertanahan
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Tanah', 'syarat' => json_encode(['Fotokopi KTP', 'Bukti Kepemilikan'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Riwayat Tanah', 'syarat' => json_encode(['Fotokopi KTP', 'Sertifikat/Girik'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Penguasaan Tanah', 'syarat' => json_encode(['Fotokopi KTP', 'Surat Pernyataan'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Jual Beli Tanah', 'syarat' => json_encode(['Fotokopi KTP Penjual & Pembeli', 'Sertifikat Asli'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Hibah Tanah', 'syarat' => json_encode(['Fotokopi KTP Pemberi & Penerima Hibah', 'Sertifikat Asli'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Keterangan Waris atas Tanah', 'syarat' => json_encode(['Fotokopi KTP Ahli Waris', 'Surat Kematian', 'Sertifikat Asli'])],
        //     ['kategori' => 'Pertanahan', 'nama' => 'Surat Rekomendasi Pendaftaran Tanah', 'syarat' => json_encode(['Fotokopi KTP', 'Bukti Kepemilikan Awal'])],
        // ];

        // foreach ($jenisSurat as $jenis) {
        //     PelayananJenis::create($jenis);
        // }
    }
}

