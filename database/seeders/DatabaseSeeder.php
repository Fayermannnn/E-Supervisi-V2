<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Organization\Actions\AssignSupervisor;
use App\Models\Dinas;
use App\Models\HelpArticle;
use App\Models\Sekolah;
use App\Models\User;
use App\Support\Enums\Role;
use App\Support\Enums\SupervisorType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // --- Admin Sistem -----------------------------------------------------
        $adminSistem = User::factory()->create([
            'name' => 'Administrator Sistem',
            'email' => 'admin.sistem@esupervisi.test',
            'password' => Hash::make('password'),
            'jabatan' => 'Administrator Sistem',
            'nip' => '999900000001',
        ]);
        $adminSistem->assignRole(Role::AdminSistem);

        // --- Dua dinas (multi-dinas via scoping, R-02) -----------------------
        $dinasMahulu = Dinas::create([
            'nama' => 'Dinas Pendidikan Kabupaten Mahakam Ulu',
            'kode' => 'MAHULU',
            'tipe' => 'kabupaten',
            'provinsi' => 'Kalimantan Timur',
        ]);

        $dinasSamarinda = Dinas::create([
            'nama' => 'Dinas Pendidikan Kota Samarinda',
            'kode' => 'SMD',
            'tipe' => 'kota',
            'provinsi' => 'Kalimantan Timur',
        ]);

        $adminDinas = User::factory()->create([
            'name' => 'Kepala Bidang GTK Mahakam Ulu',
            'email' => 'admin.dinas@esupervisi.test',
            'password' => Hash::make('password'),
            'jabatan' => 'Kepala Bidang GTK',
            'nip' => '999900000002',
        ]);
        $adminDinas->assignRole(Role::AdminDinas, $dinasMahulu);

        $this->seedDinas($dinasMahulu, wilayah: '3T', supervisorEmailPrefix: 'mahulu');
        $this->seedDinas($dinasSamarinda, wilayah: 'Kota', supervisorEmailPrefix: 'smd');

        $this->seedHelpArticles($adminSistem);
    }

    private function seedDinas(Dinas $dinas, string $wilayah, string $supervisorEmailPrefix): void
    {
        $assign = app(AssignSupervisor::class);

        $mulai = now()->startOfYear()->toDateString();

        /** @var list<Sekolah> $sekolahDibuat */
        $sekolahDibuat = [];

        foreach (['SD', 'SMP', 'SMA'] as $index => $jenjang) {
            $sekolah = Sekolah::factory()->forDinas($dinas)->create([
                'nama' => "{$jenjang} Negeri ".($index + 1).' '.$dinas->kode,
                'jenjang' => $jenjang,
                'wilayah' => $wilayah,
            ]);
            $sekolahDibuat[] = $sekolah;

            $kepsek = User::factory()->atSekolah($sekolah)->supervisor(SupervisorType::KepalaSekolah)->create([
                'name' => 'Kepala '.$sekolah->nama,
                'email' => "kepsek.{$supervisorEmailPrefix}.{$index}@esupervisi.test",
                'password' => Hash::make('password'),
            ]);

            foreach (range(0, 2) as $g) {
                $guru = User::factory()->atSekolah($sekolah)->guru()->create([
                    'name' => 'Guru '.chr(65 + $g).' '.$sekolah->nama,
                    'email' => "guru.{$supervisorEmailPrefix}.{$index}.{$g}@esupervisi.test",
                    'password' => Hash::make('password'),
                ]);

                $assign->handle($kepsek, $kepsek, $guru, $mulai);
            }
        }

        // Satu pengawas lintas sekolah dalam dinas
        $pengawas = User::factory()->atSekolah($sekolahDibuat[0])->supervisor(SupervisorType::Pengawas)->create([
            'name' => "Pengawas Pembina {$dinas->kode}",
            'email' => "pengawas.{$supervisorEmailPrefix}@esupervisi.test",
            'password' => Hash::make('password'),
        ]);

        $guruLain = User::query()
            ->whereHas('sekolah', fn ($q) => $q->where('dinas_id', $dinas->id))
            ->whereHas('roleAssignments', fn ($q) => $q->where('role', Role::Guru->value))
            ->limit(2)->get();

        foreach ($guruLain as $guru) {
            $assign->handle($pengawas, $pengawas, $guru, $mulai);
        }
    }

    private function seedHelpArticles(User $author): void
    {
        $articles = [
            ['umum', 'Memulai dengan E-Supervisi', "## Selamat datang\n\nE-Supervisi mendukung enam tahap siklus supervisi klinis: perencanaan, observasi, analisis, umpan balik, tindak lanjut, dan pelaporan.\n\nHubungi Admin Sekolah/Dinas Anda jika belum menerima akses."],
            ['akun', 'Mengganti kata sandi', 'Buka menu **Profil saya** dari pojok kanan atas, lalu bagian *Ubah kata sandi*. Jika lupa, gunakan tautan *Lupa kata sandi* di halaman masuk.'],
            ['peran', 'Peran dan hak akses', "- **Guru**: mengisi refleksi, menerima umpan balik, melaksanakan tindak lanjut.\n- **Supervisor**: menjalankan seluruh siklus untuk guru binaan.\n- **Admin Dinas**: memantau agregat lintas sekolah.\n- **Admin Sistem**: mengelola pengguna dan konfigurasi."],
        ];

        foreach ($articles as $i => [$category, $title, $body]) {
            HelpArticle::create([
                'slug' => str($title)->slug(),
                'title' => $title,
                'category' => $category,
                'body_markdown' => $body,
                'position' => $i,
                'is_published' => true,
                'updated_by' => $author->id,
            ]);
        }
    }
}
