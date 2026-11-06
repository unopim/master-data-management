<?php

return [
    'menu' => [
        'master-data' => 'Data Master',
        'icons'       => 'Ikon',
    ],

    'acl' => [
        'master-data' => 'Data Master',
        'icons'       => [
            'index'  => 'Ikon',
            'create' => 'Buat',
            'edit'   => 'Edit',
            'delete' => 'Hapus',
        ],

        'records' => [
            'erase' => 'Hapus Permanen',
        ],
    ],

    'dashboard' => [
        'title'      => 'Data Master',
        'inside'     => 'Di dalam: :types',
        'top-level'  => 'Tingkat teratas',
        'standalone' => 'Mandiri',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Data Master',
            'records'     => 'Rekaman',
            'upsert'      => 'Sisipkan atau perbarui berdasarkan ID Sistem Sumber',
        ],

        'validation-failed'     => 'Validasi gagal.',
        'change-held'           => 'Perubahan menunggu persetujuan.',
        'xref-not-found'        => 'Tidak ada rekaman yang cocok dengan ID eksternal ini.',
        'entity-type-not-found' => 'Tipe entitas tidak ditemukan.',
        'record-not-found'      => 'Rekaman tidak ditemukan.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipe Entitas',
            'source-system'       => 'Sistem Sumber',
            'code-list'           => 'Daftar Kode',
            'entity-type-info'    => 'Tipe entitas yang catatannya ada di file.',
            'source-system-info'  => 'Sistem asal file. ID eksternalnya dibaca dari kolom external_id.',
            'code-list-info'      => 'Daftar tujuan impor. Kode ditulis ke versi drafnya, yang Anda terbitkan secara terpisah.',
            'mapping-info'        => 'Satu baris per kolom yang diganti namanya: kolom file=kode field, atau kode field-locale untuk field per locale, mis. Asset Num=external_id dan Description=name. Kolom tanpa baris dibaca dengan namanya sendiri.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Pilih tipe entitas aktif yang catatannya boleh Anda buat atau edit.',
            'source-system-invalid'              => 'Pilih sistem sumber yang aktif.',
            'code-list-invalid'                  => 'Pilih daftar kode yang ada yang boleh Anda edit.',
            'external-id-without-source-system'  => 'File memiliki kolom ID eksternal (:column). Pilih sistem sumber asalnya, atau hapus kolom tersebut.',
        ],

        'schedule' => [
            'job-not-found'  => 'Tidak ada tugas impor dengan kode :code.',
            'not-masterdata' => 'Tugas :code bukan impor data master.',
            'no-user'        => 'Tentukan admin yang menjalankan impor dengan --user atau MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Tidak ada admin aktif dengan email :email.',
            'file-not-found' => 'File :path tidak ditemukan pada disk :disk.',
            'nothing'        => 'Tidak ada file yang menunggu di :path pada disk :disk.',
            'queued'         => 'File :file dimasukkan ke antrean untuk tugas :code, dilacak sebagai #:id.',
            'imported'       => 'File :file diimpor untuk tugas :code, dilacak sebagai #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demo manufaktur siap: :types tipe entitas, :systems sistem sumber, :records rekaman dalam pohon lokasi, :xrefs referensi silang, :lists daftar kode, :fields bidang, :aliases alias.',
        'invalid-template' => 'Templat :name tidak dapat dibaca atau memiliki versi format yang tidak didukung.',
    ],

    'platform-module' => [
        'name' => 'Data Master (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipe entitas yang dihapus: :count.',
        'mass-delete-skipped' => 'Tidak dihapus karena masih memiliki catatan: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipe Induk',
            'records-count'        => 'Catatan',
            'fields-count'         => 'Kolom',
            'updated-at'           => 'Diperbarui',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Urutan',
        ],

        'edit' => [
            'schema-version' => 'Skema v:version',
        ],

        'form' => [
            'basics'                    => 'Dasar',
            'identity'                  => 'Identitas',
            'hierarchy'                 => 'Hierarki',
            'governance'                => 'Tata Kelola',
            'quality-matching'          => 'Kualitas & Pencocokan',
            'matching-info'             => 'Baris masuk dari sistem sumber dicocokkan dengan rekaman yang ada di kotak masuk pencocokan.',
            'auto-match-threshold'      => 'Ambang Pencocokan Otomatis',
            'auto-match-off'            => 'Nonaktif',
            'auto-match-threshold-info' => 'Biarkan kosong untuk meninjau setiap baris. Dari 0,5 hingga 1: baris yang saran terbaiknya mencapai setidaknya nilai ini, tanpa saran lain yang sama tinggi, diterima secara otomatis. Tipe yang memerlukan persetujuan tetap menahan tautan hingga disetujui.',
            'match-on-write'            => 'Periksa Duplikat saat Menyimpan',
            'match-on-write-info'       => 'Setiap rekaman yang disimpan dibandingkan dengan rekaman lain di latar belakang menggunakan aturan pencocokan. Matikan untuk menemukan duplikat hanya saat pemindaian kualitas.',
            'manage-icons'              => 'Kelola ikon',
            'sort-order'                => 'Urutan',
            'sort-order-info'           => 'Angka yang lebih kecil ditampilkan lebih dulu, dari 0 hingga 9999; tipe dengan angka yang sama diurutkan menurut kode.',
        ],

        'quick-create' => [
            'code-info'             => 'Huruf kecil, angka, dan garis bawah, diawali huruf. Tidak dapat diubah setelah disimpan.',
            'code-pattern-info'     => 'Gunakan {seq} atau {seq:6} untuk nomor urut dan {TYPE} untuk kode tipe, misalnya {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Rekaman datang dari beberapa sistem sumber, lalu dicocokkan dan digabung menjadi satu rekaman emas.',
                'centralized'   => 'Rekaman dibuat dan dikelola di sini, lalu diterbitkan ke sistem yang menggunakannya.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Aturan pencocokan pada :target',
        ],

        'show' => [
            'auto-matched' => 'Dicocokkan otomatis',
            'auto-pending' => 'Dicocokkan secara otomatis; tautan menunggu persetujuan.',
        ],

        'datagrid' => [
            'decision' => 'Keputusan',
            'auto'     => 'Dicocokkan otomatis',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Digunakan Oleh',
            'empty'        => 'Tidak ada pekerjaan ekspor yang menerbitkan rekaman dengan tipe dan status ini.',
            'job'          => 'Pekerjaan Ekspor',
            'format'       => 'Format',
            'mode'         => 'Mode',
            'full'         => 'Penuh',
            'delta'        => 'Hanya perubahan',
            'last-run-at'  => 'Proses Terakhir Selesai',
            'no-run'       => 'Belum pernah dijalankan',
            'record-state' => 'Rekaman Ini',
            'states'       => [
                'never_run'  => 'Belum diekspor',
                'pending'    => 'Berubah sejak proses terakhir',
                'included'   => 'Termasuk dalam proses terakhir',
                'up_to_date' => 'Terkini',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikon berhasil dibuat.',
        'update-success' => 'Ikon berhasil diperbarui.',
        'delete-success' => 'Ikon berhasil dihapus.',
        'delete-in-use'  => '{1} Ikon ini ditampilkan oleh satu tipe entitas dan tidak dapat dihapus.|[2,*] Ikon ini ditampilkan oleh :count tipe entitas dan tidak dapat dihapus.',
        'index'          => [
            'title'      => 'Ikon',
            'create-btn' => 'Tambah Ikon',
        ],

        'create' => [
            'title'    => 'Tambah Ikon',
            'save-btn' => 'Simpan Ikon',
        ],

        'edit' => [
            'title'    => 'Edit Ikon',
            'save-btn' => 'Simpan Ikon',
        ],

        'form' => [
            'code'      => 'Kode',
            'name'      => 'Nama',
            'file'      => 'File Ikon',
            'file-info' => 'SVG, PNG, atau WebP, hingga :size KB. Gunakan satu bentuk pada latar belakang transparan: ikon akan mengikuti warna tema.',
            'preview'   => 'Pratinjau',
            'usage'     => '{0} Belum ada tipe entitas yang menampilkan ikon ini.|{1} Ditampilkan oleh satu tipe entitas.|[2,*] Ditampilkan oleh :count tipe entitas.',
        ],

        'library' => [
            'barcode'    => 'Barcode',
            'boxes'      => 'Material',
            'briefcase'  => 'Vendor',
            'building_2' => 'Perusahaan',
            'cpu'        => 'Aset',
            'factory'    => 'Pabrik',
            'file_text'  => 'Kontrak',
            'globe'      => 'Negara',
            'handshake'  => 'Mitra',
            'landmark'   => 'Badan Hukum',
            'map_pin'    => 'Lokasi',
            'network'    => 'Hierarki',
            'package'    => 'Produk',
            'store'      => 'Toko',
            'tag'        => 'Kategori',
            'truck'      => 'Pemasok',
            'user_round' => 'Kontak',
            'users'      => 'Pelanggan',
            'warehouse'  => 'Gudang',
            'wrench'     => 'Peralatan',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikon',
            'code'    => 'Kode',
            'name'    => 'Nama',
            'usage'   => 'Digunakan Oleh',
            'edit'    => 'Edit',
            'delete'  => 'Hapus',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Pencocokan',
        'title'          => 'Aturan Pencocokan',
        'info'           => 'Tentukan cara rekaman jenis ini dibandingkan untuk menemukan duplikat dan mencocokkan baris yang masuk. Tanpa aturan, kode dan label yang dibandingkan.',
        'create-btn'     => 'Tambah Aturan Pencocokan',
        'create-success' => 'Aturan pencocokan berhasil dibuat.',
        'update-success' => 'Aturan pencocokan berhasil diperbarui.',
        'delete-success' => 'Aturan pencocokan berhasil dihapus.',

        'create' => [
            'title' => 'Tambah Aturan Pencocokan',
        ],

        'edit' => [
            'title' => 'Edit Aturan Pencocokan',
        ],

        'form' => [
            'target'            => 'Bandingkan',
            'select-target'     => 'Pilih yang akan dibandingkan',
            'target-info'       => 'Kode rekaman, labelnya, atau field dari tipe entitas ini.',
            'comparator'        => 'Metode perbandingan',
            'select-comparator' => 'Pilih metode perbandingan',
            'comparator-info'   => 'Cara kedua nilai dibandingkan, misalnya Mirip untuk nama yang salah ketik atau Numerik untuk jumlah.',
            'weight'            => 'Bobot',
            'weight-info'       => 'Seberapa besar aturan ini dihitung, dari 0,1 hingga 100. Skornya adalah rata-rata tertimbang dari aturan yang memiliki nilai pada baris masuk.',
            'tolerance'         => 'Toleransi',
            'tolerance-info'    => 'Selisih terbesar antara dua angka yang masih dianggap mirip. Biarkan kosong agar hanya angka yang sama yang cocok.',
            'days'              => 'Rentang Tanggal (hari)',
            'days-info'         => 'Jumlah hari terbanyak yang boleh memisahkan dua tanggal agar masih dianggap mirip. Biarkan kosong agar hanya hari yang sama yang cocok.',
            'sort-order'        => 'Urutan',
            'is-blocking'       => 'Kunci Pemblokiran',
            'is-blocking-info'  => 'Cari juga rekaman yang memiliki nilai yang persis sama, meskipun kode dan labelnya tampak berbeda. Field harus dapat dicari.',
            'save-btn'          => 'Simpan Aturan Pencocokan',
        ],

        'targets' => [
            'code'  => 'Kode',
            'label' => 'Label',
        ],

        'comparators' => [
            'exact'    => 'Persis',
            'fuzzy'    => 'Mirip',
            'token'    => 'Kata yang sama',
            'phonetic' => 'Bunyi mirip',
            'numeric'  => 'Numerik',
            'date'     => 'Tanggal',
        ],

        'datagrid' => [
            'target'     => 'Bandingkan',
            'comparator' => 'Metode perbandingan',
            'weight'     => 'Bobot',
            'blocking'   => 'Kunci Pemblokiran',
            'sort-order' => 'Urutan',
            'yes'        => 'Ya',
            'no'         => 'Tidak',
            'edit'       => 'Edit',
            'delete'     => 'Hapus',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Hanya kode, label, atau field yang dapat dicari yang bisa menjadi kunci pemblokiran.',
            'too-many'                => 'Satu tipe entitas dapat memiliki paling banyak :count aturan pencocokan.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Penentuan Data Utama',
        'title'          => 'Aturan Penentuan Data Utama',
        'info'           => 'Tentukan nilai mana yang dipertahankan golden record saat dua rekaman bertipe ini digabungkan. Tanpa aturan, rekaman yang bertahan mempertahankan nilainya kecuali kosong.',
        'create-btn'     => 'Tambah Aturan Penentuan Data Utama',
        'create-success' => 'Aturan penentuan data utama berhasil dibuat.',
        'update-success' => 'Aturan penentuan data utama berhasil diperbarui.',
        'delete-success' => 'Aturan penentuan data utama berhasil dihapus.',

        'create' => [
            'title' => 'Tambah Aturan Penentuan Data Utama',
        ],

        'edit' => [
            'title' => 'Edit Aturan Penentuan Data Utama',
        ],

        'form' => [
            'target'            => 'Berlaku untuk',
            'select-target'     => 'Pilih target penerapan aturan',
            'target-info'       => 'Sebuah bidang, label rekaman, atau semua bidang yang tidak memiliki aturan sendiri.',
            'strategy'          => 'Strategi',
            'select-strategy'   => 'Pilih strategi',
            'strategy-info'     => 'Cara nilai yang bertahan dipilih. Steward data tetap dapat mengubah setiap pilihan di layar penggabungan.',
            'source-order'      => 'Urutan Sumber',
            'select-sources'    => 'Pilih sistem sumber',
            'source-order-info' => 'Sistem sumber paling tepercaya didahulukan. Sistem yang tidak disebut mengikuti prioritasnya. Kosongkan untuk hanya memakai prioritas sistem sumber.',
            'sort-order'        => 'Urutan',
            'save-btn'          => 'Simpan Aturan Penentuan Data Utama',
        ],

        'targets' => [
            'default' => 'Default untuk semua bidang',
            'label'   => 'Label',
        ],

        'strategies' => [
            'source_priority' => 'Sumber paling tepercaya',
            'most_recent'     => 'Terakhir diperbarui',
            'most_complete'   => 'Paling lengkap',
            'keep_target'     => 'Pertahankan rekaman yang bertahan',
            'manual'          => 'Steward data yang memutuskan',
        ],

        'datagrid' => [
            'target'     => 'Berlaku untuk',
            'strategy'   => 'Strategi',
            'sort-order' => 'Urutan',
            'edit'       => 'Edit',
            'delete'     => 'Hapus',
        ],

        'validation' => [
            'target-taken' => 'Tipe entitas ini sudah memiliki aturan penentuan data utama untuk target ini.',
            'too-many'     => 'Satu tipe entitas dapat memiliki paling banyak :count aturan penentuan data utama.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validasi',
        'title'          => 'Aturan Validasi',
        'info'           => 'Periksa nilai bidang lebih dari sekadar tipenya. Aturan pemblokir menolak penyimpanan, impor, atau penulisan API; aturan peringatan meloloskannya dan memunculkan peringatan kualitas data.',
        'create-btn'     => 'Tambah Aturan Validasi',
        'create-success' => 'Aturan validasi berhasil dibuat.',
        'update-success' => 'Aturan validasi berhasil diperbarui.',
        'delete-success' => 'Aturan validasi berhasil dihapus.',

        'create' => [
            'title' => 'Tambah Aturan Validasi',
        ],

        'edit' => [
            'title' => 'Ubah Aturan Validasi',
        ],

        'form' => [
            'field'            => 'Bidang',
            'select-field'     => 'Pilih bidang',
            'type'             => 'Aturan',
            'select-type'      => 'Pilih aturan',
            'severity'         => 'Tingkat Keparahan',
            'severity-info'    => '"Blokir" menolak nilai yang melanggar aturan. "Peringatkan" menyimpannya dan melaporkan peringatan kualitas.',
            'pattern'          => 'Pola',
            'pattern-info'     => 'Ekspresi reguler dengan pembatas, misalnya /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Nilai',
            'min-length'       => 'Panjang Minimum',
            'max-length'       => 'Panjang Maksimum',
            'operator'         => 'Perbandingan',
            'other-field'      => 'Dibandingkan Dengan',
            'other-field-info' => 'Aturan terpenuhi selama bidang lainnya kosong.',
            'codes'            => 'Kode yang Diizinkan',
            'codes-info'       => 'Satu kode per baris atau dipisahkan koma. Nilai dibandingkan secara persis.',
            'after'            => 'Tidak Sebelum',
            'before'           => 'Tidak Setelah',
            'date-info'        => 'Tanggal seperti 2020-01-01 atau tanggal relatif seperti today atau -18 years. Kosongkan salah satunya untuk rentang terbuka.',
            'messages'         => 'Pesan Kesalahan',
            'messages-info'    => 'Ditampilkan saat nilai melanggar aturan, dalam bahasa orang atau klien API. Kosongkan untuk pesan default. :attribute mewakili nama bidang.',
            'sort-order'       => 'Urutan Pengurutan',
            'save-btn'         => 'Simpan Aturan Validasi',
        ],

        'types' => [
            'regex'        => 'Cocok dengan pola',
            'min'          => 'Nilai minimum',
            'max'          => 'Nilai maksimum',
            'length'       => 'Panjang',
            'cross_field'  => 'Dibandingkan dengan bidang lain',
            'one_of_codes' => 'Salah satu kode yang tercantum',
            'date_range'   => 'Rentang tanggal',
        ],

        'severities' => [
            'block' => 'Blokir',
            'warn'  => 'Peringatkan',
        ],

        'operators' => [
            'eq'  => 'sama dengan',
            'neq' => 'berbeda dari',
            'gt'  => 'lebih besar dari',
            'gte' => 'minimal sama dengan',
            'lt'  => 'lebih kecil dari',
            'lte' => 'maksimal sama dengan',
        ],

        'messages' => [
            'default'      => ':attribute tidak valid.',
            'regex'        => ':attribute tidak sesuai dengan format yang diharapkan.',
            'min'          => ':attribute minimal harus :value.',
            'max'          => ':attribute maksimal harus :value.',
            'length'       => ':attribute harus memiliki panjang antara :min dan :max karakter.',
            'cross_field'  => ':attribute harus :operator :field.',
            'one_of_codes' => ':attribute harus salah satu dari: :codes.',
            'date_range'   => ':attribute harus berupa tanggal dalam rentang yang diizinkan.',
            'unavailable'  => 'Pemeriksaan :rule pada :attribute tidak lagi tersedia. Minta administrator untuk menghapus atau mengganti aturan tersebut.',
        ],

        'datagrid' => [
            'field'      => 'Bidang',
            'type'       => 'Aturan',
            'severity'   => 'Tingkat Keparahan',
            'sort-order' => 'Urutan Pengurutan',
            'edit'       => 'Ubah',
            'delete'     => 'Hapus',
        ],

        'validation' => [
            'too-many'     => 'Satu tipe entitas dapat memiliki maksimal :count aturan validasi.',
            'invalid-date' => 'Masukkan tanggal atau tanggal relatif seperti today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Standardisasi',
        'info'           => 'Bersihkan nilai sebelum divalidasi dan disimpan, baik dari admin, impor, maupun API. Transformasi suatu bidang berjalan sesuai urutan pengurutannya.',
        'create-btn'     => 'Tambah Transformasi',
        'create-success' => 'Transformasi berhasil dibuat.',
        'update-success' => 'Transformasi berhasil diperbarui.',
        'delete-success' => 'Transformasi berhasil dihapus.',
        'unavailable'    => 'Transformasi :transform pada :attribute tidak lagi tersedia. Minta administrator untuk menghapus atau menggantinya.',

        'create' => [
            'title' => 'Tambah Transformasi',
        ],

        'edit' => [
            'title' => 'Ubah Transformasi',
        ],

        'form' => [
            'field'            => 'Bidang',
            'select-field'     => 'Pilih bidang',
            'transform'        => 'Transformasi',
            'select-transform' => 'Pilih transformasi',
            'transform-info'   => 'Hanya berlaku untuk nilai teks; nilai lain dibiarkan apa adanya.',
            'sort-order'       => 'Urutan Pengurutan',
            'sort-order-info'  => 'Transformasi pada bidang yang sama berjalan dari urutan terendah ke atas.',
            'save-btn'         => 'Simpan Transformasi',
        ],

        'types' => [
            'trim'            => 'Pangkas spasi di sekitarnya',
            'lower'           => 'Huruf kecil',
            'upper'           => 'Huruf besar',
            'title'           => 'Huruf kapital di awal kata',
            'collapse_spaces' => 'Ringkas spasi berulang',
            'email_normalize' => 'Normalisasi alamat email',
            'phone_e164'      => 'Normalisasi nomor telepon (E.164)',
            'strip_non_alnum' => 'Pertahankan hanya huruf dan angka',
        ],

        'datagrid' => [
            'field'      => 'Bidang',
            'transform'  => 'Transformasi',
            'sort-order' => 'Urutan Pengurutan',
            'edit'       => 'Ubah',
            'delete'     => 'Hapus',
        ],

        'validation' => [
            'taken'    => 'Bidang ini sudah menjalankan transformasi ini.',
            'too-many' => 'Satu tipe entitas dapat memiliki maksimal :count transformasi.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Peringatan validasi',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Jelajahi :type di bawahnya',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Tingkat Teratas',
        ],

        'datagrid' => [
            'erase'         => 'Hapus Permanen',
            'parent'        => 'Induk',
            'top-level'     => 'Tingkat teratas',
            'children'      => 'Turunan',
            'view-children' => 'Tampilkan :count catatan di bawah catatan ini',
        ],

        'erase' => [
            'btn'             => 'Hapus Permanen',
            'confirm-title'   => 'Hapus catatan ini secara permanen?',
            'confirm-message' => 'Semua nilai, label, dan kode catatan ini serta duplikat yang digabungkan ke dalamnya akan dihapus, beserta riwayat, permintaan perubahan, dan masalah kualitasnya. Catatan tetap ada sebagai placeholder terhapus agar ID sumber dan tautannya tetap dapat ditemukan. Tindakan ini tidak dapat dibatalkan.',
            'success'         => 'Catatan dihapus secara permanen.',
            'not-retired'     => 'Hanya catatan yang dipensiunkan yang dapat dihapus. Pensiunkan terlebih dahulu.',
        ],

        'mass-retire' => [
            'success' => ':count catatan dipensiunkan.',
            'pending' => ':count pensiun diajukan untuk persetujuan.',
            'skipped' => ':count catatan dilewati karena bukan draf atau aktif, atau telah diubah oleh orang lain.',
            'none'    => 'Tidak ada catatan terpilih yang dapat dipensiunkan.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Perlu persetujuan: permintaan perubahan #:id',
            'description'           => ':requester mengajukan perubahan ":operation" pada :type untuk Anda setujui.',
            'description-automated' => 'Perubahan otomatis ":operation" pada :type menunggu persetujuan Anda.',
        ],
    ],

    'access' => [
        'menu'           => 'Akses Data',
        'acl'            => [
            'index'  => 'Akses Data',
            'create' => 'Buat',
            'edit'   => 'Edit',
            'delete' => 'Hapus',
        ],
        'principals' => [
            'role' => 'Peran',
            'user' => 'Pengguna',
        ],
        'actions' => [
            'view'    => 'Lihat',
            'edit'    => 'Edit',
            'approve' => 'Setujui',
        ],
        'index' => [
            'title'      => 'Akses Data',
            'create-btn' => 'Tambah Izin',
            'info'       => 'Izin menentukan rekaman tipe entitas terbatas mana yang dapat diakses setiap peran atau pengguna: rekaman yang dipilih dan semua yang ada di bawahnya. Akses dari peran pengguna dan dari izin yang diberikan kepada pengguna digabungkan.',
        ],
        'create' => [
            'title' => 'Tambah Izin Akses Data',
        ],
        'edit' => [
            'title' => 'Edit Izin Akses Data',
        ],
        'form' => [
            'save-btn'         => 'Simpan Izin',
            'principal'        => 'Diberikan Kepada',
            'principal-info'   => 'Izin peran berlaku untuk setiap pengguna dengan peran tersebut. Berikan kepada satu pengguna untuk pengecualian.',
            'principal-type'   => 'Berikan Kepada',
            'role'             => 'Peran',
            'user'             => 'Pengguna',
            'scope'            => 'Cakupan',
            'scope-info'       => 'Izin ini mencakup rekaman yang dipilih dan setiap rekaman di bawahnya dalam hierarki.',
            'root'             => 'Akar Subpohon',
            'root-info'        => 'Misalnya sebuah situs atau pabrik. Rekaman di bawahnya mewarisi izin ini.',
            'entity-type'      => 'Tipe Entitas',
            'entity-type-info' => 'Batasi izin pada satu tipe entitas terbatas, atau kosongkan untuk semua tipe terbatas.',
            'any-entity-type'  => 'Semua tipe entitas terbatas',
            'actions'          => 'Tindakan',
            'actions-info'     => 'Lihat membaca rekaman, Edit mengubah, menonaktifkan, menggabungkan, dan membuat rekaman di bawah akar, Setujui memutuskan perubahan yang tertahan. Edit dan Setujui sudah termasuk Lihat.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipe',
            'principal'         => 'Diberikan Kepada',
            'missing-principal' => 'Dihapus',
            'entity-type'       => 'Tipe Entitas',
            'root'              => 'Akar Subpohon',
            'actions'           => 'Tindakan',
            'edit'              => 'Edit',
            'delete'            => 'Hapus',
        ],
        'role-card' => [
            'title'  => 'Akses Master Data',
            'info'   => 'Rekaman tipe entitas terbatas mana yang dapat diakses pengguna dengan peran ini. Setiap izin mencakup rekaman akarnya dan semua yang ada di bawahnya.',
            'empty'  => 'Peran ini belum memiliki izin akses master data.',
            'remove' => 'Hapus',
            'add'    => 'Tambah izin',
        ],
        'entity-type' => [
            'is-scoped'      => 'Batasi Akses berdasarkan Subpohon',
            'is-scoped-info' => 'Jika aktif, pengguna hanya dapat mengakses rekaman tipe ini di dalam subpohon yang diberikan kepada peran mereka atau kepada mereka di layar Akses Data. Biarkan nonaktif untuk data referensi bersama.',
        ],
        'validation' => [
            'record-not-found'    => 'Pilih rekaman yang dapat Anda lihat sebagai akar subpohon.',
            'parent-out-of-scope' => 'Pilih induk di dalam subpohon tempat Anda memiliki akses edit.',
        ],
        'create-success' => 'Izin akses data ditambahkan.',
        'update-success' => 'Izin akses data diperbarui.',
        'delete-success' => 'Izin akses data dihapus.',
    ],

    'install' => [
        'migrating' => 'Menjalankan migrasi data master.',
        'demo'      => 'Memasang data demo manufaktur.',
        'done'      => 'Manajemen data master telah terpasang. Modul aktif: :modules.',
    ],
];
