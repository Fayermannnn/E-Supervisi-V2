<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Domain\Analysis\Actions\FinalizeAnalysis;
use App\Domain\Analysis\Actions\PerformAnalysis;
use App\Domain\Analysis\Actions\SaveAnalysisSummary;
use App\Domain\Feedback\Actions\AcknowledgeFeedback;
use App\Domain\Feedback\Actions\PostFeedbackMessage;
use App\Domain\Feedback\Actions\StartFeedbackSession;
use App\Domain\FollowUp\Actions\CreateFollowUpPlan;
use App\Domain\Instruments\FormatBTemplate;
use App\Domain\Observation\Actions\FinalizeObservation;
use App\Domain\Observation\Actions\SaveObservation;
use App\Domain\Observation\Actions\StartObservation;
use App\Domain\Organization\Actions\AssignSupervisor;
use App\Domain\Planning\Actions\RecordPlanningAgreementConsent;
use App\Domain\Planning\Actions\SavePlanningAgreement;
use App\Domain\Supervision\Actions\CreateCycle;
use App\Models\Dinas;
use App\Models\HelpArticle;
use App\Models\Instrument;
use App\Models\InstrumentVersion;
use App\Models\Sekolah;
use App\Models\SupervisorAssignment;
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

        $this->call(AiPromptTemplateSeeder::class);

        $instrument = $this->seedFormatB($adminSistem);

        $this->seedDinas($dinasMahulu, wilayah: '3T', supervisorEmailPrefix: 'mahulu');
        $this->seedDinas($dinasSamarinda, wilayah: 'Kota', supervisorEmailPrefix: 'smd');

        $this->seedCycles($instrument->versions()->firstOrFail());

        $this->seedHelpArticles($adminSistem);
    }

    private function seedFormatB(User $author): Instrument
    {
        $instrument = Instrument::create([
            'code' => 'B',
            'nama' => 'Format B — Observasi Pelaksanaan Pembelajaran (CONTOH)',
            'deskripsi' => 'Instrumen contoh untuk demo; belum tervalidasi (Artikel 2).',
            'pemilik_dinas_id' => null,
            'status' => 'published',
        ]);

        InstrumentVersion::create([
            'instrument_id' => $instrument->id,
            'version' => 1,
            'schema_json' => FormatBTemplate::schema(),
            'scoring_config' => FormatBTemplate::scoringConfig(),
            'catatan_perubahan' => 'Versi awal (contoh).',
            'published_at' => now(),
            'created_by' => $author->id,
        ]);

        return $instrument;
    }

    private function seedCycles(InstrumentVersion $version): void
    {
        $assignments = SupervisorAssignment::query()->with(['supervisor', 'guru'])->limit(8)->get();

        foreach ($assignments as $i => $assignment) {
            $supervisor = $assignment->supervisor;
            $guru = $assignment->guru;
            if ($supervisor === null || $guru === null) {
                continue;
            }

            $cycle = app(CreateCycle::class)->handle($supervisor, $guru, '2026/2027', 'ganjil', 'Supervisi Pembelajaran '.$guru->name);
            if ($i === 0) {
                continue; // Draf
            }

            app(SavePlanningAgreement::class)->handle($supervisor, $cycle, [
                'fokus_observasi' => 'Pengelolaan kelas dan aktivasi peserta didik',
                'instrument_version_id' => $version->id,
                'tipe_observasi' => 'sinkron',
                'jadwal_mulai' => now()->addDays(3)->setTime(8, 0)->toDateTimeString(),
                'kelas' => 'VIII-A',
                'mata_pelajaran' => 'Matematika',
            ]);
            app(RecordPlanningAgreementConsent::class)->handle($guru, $cycle);
            app(RecordPlanningAgreementConsent::class)->handle($supervisor, $cycle);
            if ($i === 1) {
                continue; // Terjadwal
            }

            $observation = app(StartObservation::class)->handle($supervisor, $cycle->refresh());
            $rows = [];
            foreach ($version->schema()->requiredItemKeys() as $key) {
                $rows[] = ['item_key' => $key, 'section_key' => 'inti', 'value' => random_int(2, 4)];
            }
            app(SaveObservation::class)->handle($observation, $rows, ['catatan_skrip' => 'Observasi berjalan lancar; peserta didik antusias.']);
            if ($i === 2) {
                continue; // Terjadwal + observasi draft
            }

            app(FinalizeObservation::class)->handle($supervisor, $observation->refresh());
            if ($i === 3) {
                continue; // Observasi Selesai
            }

            $analysis = app(PerformAnalysis::class)->handle($supervisor, $cycle->refresh());
            app(SaveAnalysisSummary::class)->handle($supervisor, $analysis, 'Guru menunjukkan penguasaan materi yang baik. Area yang perlu diperkuat adalah aktivasi peserta didik pada kegiatan inti dan refleksi di penutup.');
            app(FinalizeAnalysis::class)->handle($supervisor, $analysis->refresh());
            if ($i === 4) {
                continue; // Analisis Selesai
            }

            $session = app(StartFeedbackSession::class)->handle($cycle->refresh());
            app(PostFeedbackMessage::class)->handle($supervisor, $session, 'observasi', 'Terima kasih, pengelolaan kelas sudah tertata. Mari kita bahas aktivasi peserta didik.');
            app(PostFeedbackMessage::class)->handle($guru, $session, 'tanggapan', 'Baik, saya rasa memang perlu lebih banyak pertanyaan terbuka.');
            app(PostFeedbackMessage::class)->handle($supervisor, $session, 'kesepakatan', 'Sepakat: pada 2 pertemuan berikutnya menerapkan diskusi kelompok terstruktur.');
            app(AcknowledgeFeedback::class)->handle($guru, $session);
            if ($i === 5) {
                continue; // Umpan Balik Diberikan
            }

            // Tenggat lampau untuk i>=7 agar demo eskalasi RTL terlihat.
            $tenggat = $i >= 7 ? now()->subDays(5)->toDateString() : now()->addWeeks(4)->toDateString();

            app(CreateFollowUpPlan::class)->handle($supervisor, $cycle->refresh(), 'Meningkatkan partisipasi aktif peserta didik pada kegiatan inti.', $tenggat, [
                ['deskripsi' => 'Menyusun 3 pertanyaan pemantik per pertemuan', 'indikator_keberhasilan' => 'RPP memuat pertanyaan pemantik'],
                ['deskripsi' => 'Menerapkan diskusi kelompok', 'indikator_keberhasilan' => 'Terlaksana pada 2 pertemuan'],
            ]);
        }

        // Jalankan deteksi keterlambatan agar status siklus demo konsisten.
        app(\App\Domain\FollowUp\Actions\DetectOverdueFollowUps::class)->handle();
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
