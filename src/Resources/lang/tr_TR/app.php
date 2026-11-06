<?php

return [
    'menu' => [
        'master-data' => 'Ana Veri',
        'icons'       => 'Simgeler',
    ],

    'acl' => [
        'master-data' => 'Ana Veri',
        'icons'       => [
            'index'  => 'Simgeler',
            'create' => 'Oluştur',
            'edit'   => 'Düzenle',
            'delete' => 'Sil',
        ],

        'records' => [
            'erase' => 'Kalıcı Olarak Sil',
        ],

        'entity-types' => [
            'templates' => 'Şablonları yükle',
        ],
    ],

    'dashboard' => [
        'title'      => 'Ana Veri',
        'inside'     => 'İçinde: :types',
        'top-level'  => 'En üst düzey',
        'standalone' => 'Bağımsız',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Ana Veri',
            'records'     => 'Kayıtlar',
            'upsert'      => 'Kaynak Sistem Kimliğine göre ekle veya güncelle',
        ],

        'validation-failed'     => 'Doğrulama başarısız oldu.',
        'change-held'           => 'Değişiklik onay bekliyor.',
        'xref-not-found'        => 'Bu harici kimlikle eşleşen kayıt yok.',
        'entity-type-not-found' => 'Varlık türü bulunamadı.',
        'record-not-found'      => 'Kayıt bulunamadı.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Varlık türü',
            'source-system'       => 'Kaynak sistem',
            'code-list'           => 'Kod listesi',
            'entity-type-info'    => 'Dosyanın kayıtlarını içerdiği varlık türü.',
            'source-system-info'  => 'Dosyanın geldiği sistem. Harici kimlikleri external_id sütunundan okunur.',
            'code-list-info'      => 'İçe aktarılacak liste. Kodlar, ayrıca yayımladığınız taslak sürümüne yazılır.',
            'mapping-info'        => 'Yeniden adlandırılan her sütun için bir satır: dosya sütunu=alan kodu veya yerel ayara göre bir alan için alan kodu-yerel ayar, örn. Asset Num=external_id ve Description=name. Satırı olmayan sütunlar kendi adlarıyla okunur.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Kayıtlarını oluşturabileceğiniz veya düzenleyebileceğiniz etkin bir varlık türü seçin.',
            'source-system-invalid'              => 'Etkin bir kaynak sistem seçin.',
            'code-list-invalid'                  => 'Düzenleyebileceğiniz mevcut bir kod listesi seçin.',
            'external-id-without-source-system'  => 'Dosyada bir harici kimlik sütunu (:column) var. Geldiği kaynak sistemi seçin veya sütunu kaldırın.',
        ],

        'schedule' => [
            'job-not-found'  => 'Hiçbir içe aktarma işinin kodu :code değil.',
            'not-masterdata' => ':code işi bir ana veri içe aktarması değil.',
            'no-user'        => 'İçe aktarmanın hangi yönetici olarak çalışacağını --user veya MASTERDATA_IMPORT_USER ile belirtin.',
            'user-not-found' => 'E-postası :email olan etkin bir yönetici yok.',
            'file-not-found' => ':path dosyası :disk diskinde bulunamadı.',
            'nothing'        => ':disk diskinde :path içinde bekleyen dosya yok.',
            'queued'         => ':file dosyası :code işi için kuyruğa alındı, #:id olarak izleniyor.',
            'imported'       => ':file dosyası :code işi için içe aktarıldı, #:id olarak izleniyor.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Üretim demosu hazır: :types varlık türü, :systems kaynak sistem, tesis ağacında :records kayıt, :xrefs çapraz referans, :lists kod listesi, :fields alan, :aliases takma ad.',
        'invalid-template' => 'Şablon :name okunamadı veya biçim sürümü desteklenmiyor.',
    ],

    'platform-module' => [
        'name' => 'Ana Veri (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Silinen varlık türleri: :count.',
        'mass-delete-skipped' => 'Hâlâ kayıtları olduğu için silinmedi: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Üst türler',
            'records-count'        => 'Kayıtlar',
            'fields-count'         => 'Alanlar',
            'updated-at'           => 'Güncellendi',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sıralama',
        ],

        'edit' => [
            'schema-version' => 'Şema v:version',
        ],

        'form' => [
            'basics'                    => 'Temel Bilgiler',
            'identity'                  => 'Kimlik',
            'hierarchy'                 => 'Hiyerarşi',
            'governance'                => 'Yönetişim',
            'quality-matching'          => 'Kalite ve Eşleştirme',
            'matching-info'             => 'Kaynak sistemlerden gelen satırlar, eşleştirme kutusunda mevcut kayıtlarla eşleştirilir.',
            'auto-match-threshold'      => 'Otomatik Eşleştirme Eşiği',
            'auto-match-off'            => 'Kapalı',
            'auto-match-threshold-info' => 'Her satırı incelemek için boş bırakın. 0,5 ile 1 arası: en iyi önerisi en az bu değere ulaşan ve başka hiçbir önerinin bu kadar yüksek olmadığı satır otomatik olarak kabul edilir. Onay gerektiren türlerde bağlantı yine de onay bekler.',
            'match-on-write'            => 'Kaydederken Yinelenenleri Denetle',
            'match-on-write-info'       => 'Kaydedilen her kayıt, eşleştirme kuralları kullanılarak arka planda diğer kayıtlarla karşılaştırılır. Yinelenenleri yalnızca kalite taramalarında bulmak için kapatın.',
            'manage-icons'              => 'Simgeleri yönet',
            'sort-order'                => 'Sıralama',
            'sort-order-info'           => 'Küçük numaralar önce listelenir, 0 ile 9999 arası; aynı numaraya sahip türler koda göre sıralanır.',
        ],

        'quick-create' => [
            'code-info'             => 'Harfle başlayan küçük harfler, rakamlar ve alt çizgiler. Kaydedildikten sonra değiştirilemez.',
            'code-pattern-info'     => 'Sıra numarası için {seq} veya {seq:6}, tür kodu için {TYPE} kullanın; örneğin {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Kayıtlar birden çok kaynak sistemden gelir; eşleştirilip tek bir altın kayıtta birleştirilir.',
                'centralized'   => 'Kayıtlar burada oluşturulup yönetilir, ardından bunları kullanan sistemlere yayımlanır.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => ':target üzerinde eşleştirme kuralı',
        ],

        'show' => [
            'auto-matched' => 'Otomatik eşleştirildi',
            'auto-pending' => 'Otomatik eşleştirildi; bağlantı onay bekliyor.',
        ],

        'datagrid' => [
            'decision' => 'Karar',
            'auto'     => 'Otomatik eşleştirildi',
            'manual'   => 'Manuel',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Kullanan',
            'empty'        => 'Bu tür ve durumdaki kayıtları yayımlayan bir dışa aktarma işi yok.',
            'job'          => 'Dışa Aktarma İşi',
            'format'       => 'Biçim',
            'mode'         => 'Mod',
            'full'         => 'Tam',
            'delta'        => 'Yalnızca değişiklikler',
            'last-run-at'  => 'Son Tamamlanan Çalıştırma',
            'no-run'       => 'Hiç çalıştırılmadı',
            'record-state' => 'Bu Kayıt',
            'states'       => [
                'never_run'  => 'Henüz dışa aktarılmadı',
                'pending'    => 'Son çalıştırmadan beri değişti',
                'included'   => 'Son çalıştırmaya dahil',
                'up_to_date' => 'Güncel',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Simge başarıyla oluşturuldu.',
        'update-success' => 'Simge başarıyla güncellendi.',
        'delete-success' => 'Simge başarıyla silindi.',
        'delete-in-use'  => '{1} Bu simge bir varlık türü tarafından kullanıldığı için silinemez.|[2,*] Bu simge :count varlık türü tarafından kullanıldığı için silinemez.',
        'index'          => [
            'title'      => 'Simgeler',
            'create-btn' => 'Simge Ekle',
        ],

        'create' => [
            'title'    => 'Simge Ekle',
            'save-btn' => 'Simgeyi Kaydet',
        ],

        'edit' => [
            'title'    => 'Simgeyi Düzenle',
            'save-btn' => 'Simgeyi Kaydet',
        ],

        'form' => [
            'code'      => 'Kod',
            'name'      => 'Ad',
            'file'      => 'Simge Dosyası',
            'file-info' => 'SVG, PNG veya WebP, en fazla :size KB. Şeffaf bir arka plan üzerinde tek bir şekil kullanın: tema rengini alır.',
            'preview'   => 'Önizleme',
            'usage'     => '{0} Bu simgeyi henüz kullanan bir varlık türü yok.|{1} Bir varlık türü tarafından kullanılıyor.|[2,*] :count varlık türü tarafından kullanılıyor.',
        ],

        'library' => [
            'barcode'    => 'Barkod',
            'boxes'      => 'Malzemeler',
            'briefcase'  => 'Satıcı',
            'building_2' => 'Şirket',
            'cpu'        => 'Varlık',
            'factory'    => 'Tesis',
            'file_text'  => 'Sözleşme',
            'globe'      => 'Ülke',
            'handshake'  => 'Ortak',
            'landmark'   => 'Tüzel kişi',
            'map_pin'    => 'Konum',
            'network'    => 'Hiyerarşi',
            'package'    => 'Ürün',
            'store'      => 'Mağaza',
            'tag'        => 'Kategori',
            'truck'      => 'Tedarikçi',
            'user_round' => 'Kişi',
            'users'      => 'Müşteri',
            'warehouse'  => 'Depo',
            'wrench'     => 'Ekipman',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Simge',
            'code'    => 'Kod',
            'name'    => 'Ad',
            'usage'   => 'Kullanan',
            'edit'    => 'Düzenle',
            'delete'  => 'Sil',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Eşleştirme',
        'title'          => 'Eşleştirme Kuralları',
        'info'           => 'Bu türdeki kayıtların, yinelenenleri bulmak ve gelen satırları eşleştirmek için nasıl karşılaştırılacağını belirleyin. Kural yoksa kod ve etiket karşılaştırılır.',
        'create-btn'     => 'Eşleştirme Kuralı Ekle',
        'create-success' => 'Eşleştirme kuralı başarıyla oluşturuldu.',
        'update-success' => 'Eşleştirme kuralı başarıyla güncellendi.',
        'delete-success' => 'Eşleştirme kuralı başarıyla silindi.',

        'create' => [
            'title' => 'Eşleştirme Kuralı Ekle',
        ],

        'edit' => [
            'title' => 'Eşleştirme Kuralını Düzenle',
        ],

        'form' => [
            'target'            => 'Karşılaştır',
            'select-target'     => 'Neyin karşılaştırılacağını seçin',
            'target-info'       => 'Kayıt kodu, etiketi veya bu varlık türünün bir alanı.',
            'comparator'        => 'Karşılaştırma yöntemi',
            'select-comparator' => 'Bir karşılaştırma yöntemi seçin',
            'comparator-info'   => 'İki değerin nasıl karşılaştırılacağı; örneğin yazım hatalı adlar için Yaklaşık, tutarlar için Sayısal.',
            'weight'            => 'Ağırlık',
            'weight-info'       => 'Bu kuralın ne kadar etkili olduğu, 0,1 ile 100 arası. Puan, gelen satırın değeri olan kuralların ağırlıklı ortalamasıdır.',
            'tolerance'         => 'Tolerans',
            'tolerance-info'    => 'İki sayı arasında hâlâ benzer sayılan en büyük fark. Yalnızca eşit sayıları eşleştirmek için boş bırakın.',
            'days'              => 'Tarih Aralığı (gün)',
            'days-info'         => 'İki tarihin benzer sayılması için aralarında olabilecek en fazla gün sayısı. Yalnızca aynı günü eşleştirmek için boş bırakın.',
            'sort-order'        => 'Sıralama',
            'is-blocking'       => 'Bloklama Anahtarı',
            'is-blocking-info'  => 'Kodu ve etiketi farklı görünse bile tam olarak bu değeri paylaşan kayıtları da arar. Alanlar aranabilir olmalıdır.',
            'save-btn'          => 'Eşleştirme Kuralını Kaydet',
        ],

        'targets' => [
            'code'  => 'Kod',
            'label' => 'Etiket',
        ],

        'comparators' => [
            'exact'    => 'Tam',
            'fuzzy'    => 'Yaklaşık',
            'token'    => 'Aynı kelimeler',
            'phonetic' => 'Benzer ses',
            'numeric'  => 'Sayısal',
            'date'     => 'Tarih',
        ],

        'datagrid' => [
            'target'     => 'Karşılaştır',
            'comparator' => 'Karşılaştırma yöntemi',
            'weight'     => 'Ağırlık',
            'blocking'   => 'Bloklama Anahtarı',
            'sort-order' => 'Sıralama',
            'yes'        => 'Evet',
            'no'         => 'Hayır',
            'edit'       => 'Düzenle',
            'delete'     => 'Sil',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Yalnızca kod, etiket veya aranabilir bir alan bloklama anahtarı olabilir.',
            'too-many'                => 'Bir varlık türünün en fazla :count eşleştirme kuralı olabilir.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Değer Önceliği',
        'title'          => 'Değer Önceliği Kuralları',
        'info'           => 'Bu türdeki iki kayıt birleştirildiğinde altın kaydın hangi değeri koruyacağını belirleyin. Kural yoksa kalan kayıt, boş olmadığı sürece kendi değerini korur.',
        'create-btn'     => 'Değer Önceliği Kuralı Ekle',
        'create-success' => 'Değer önceliği kuralı başarıyla oluşturuldu.',
        'update-success' => 'Değer önceliği kuralı başarıyla güncellendi.',
        'delete-success' => 'Değer önceliği kuralı başarıyla silindi.',

        'create' => [
            'title' => 'Değer Önceliği Kuralı Ekle',
        ],

        'edit' => [
            'title' => 'Değer Önceliği Kuralını Düzenle',
        ],

        'form' => [
            'target'            => 'Uygulandığı yer',
            'select-target'     => 'Kuralın neye uygulanacağını seçin',
            'target-info'       => 'Bir alan, kaydın etiketi veya kendi kuralı olmayan tüm alanlar.',
            'strategy'          => 'Strateji',
            'select-strategy'   => 'Bir strateji seçin',
            'strategy-info'     => 'Kalan değerin nasıl seçileceği. Veri sorumluları birleştirme ekranında her seçimi yine de değiştirebilir.',
            'source-order'      => 'Kaynak Sırası',
            'select-sources'    => 'Kaynak sistemleri seçin',
            'source-order-info' => 'En güvenilir kaynak sistem önce gelir. Listede olmayan sistemler kendi önceliğini izler. Yalnızca kaynak sistem önceliğini kullanmak için boş bırakın.',
            'sort-order'        => 'Sıralama',
            'save-btn'          => 'Değer Önceliği Kuralını Kaydet',
        ],

        'targets' => [
            'default' => 'Tüm alanlar için varsayılan',
            'label'   => 'Etiket',
        ],

        'strategies' => [
            'source_priority' => 'En güvenilir kaynak',
            'most_recent'     => 'En son güncellenen',
            'most_complete'   => 'En eksiksiz',
            'keep_target'     => 'Kalan kaydı koru',
            'manual'          => 'Veri sorumlusu karar verir',
        ],

        'datagrid' => [
            'target'     => 'Uygulandığı yer',
            'strategy'   => 'Strateji',
            'sort-order' => 'Sıralama',
            'edit'       => 'Düzenle',
            'delete'     => 'Sil',
        ],

        'validation' => [
            'target-taken' => 'Bu varlık türünün bu hedef için zaten bir değer önceliği kuralı var.',
            'too-many'     => 'Bir varlık türünün en fazla :count değer önceliği kuralı olabilir.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Alttaki :type kayıtlarına göz at',
    ],

    'records' => [
        'index' => [
            'top-level' => 'En Üst Düzey',
        ],

        'datagrid' => [
            'erase'         => 'Kalıcı Olarak Sil',
            'parent'        => 'Üst öğe',
            'top-level'     => 'En üst düzey',
            'children'      => 'Alt öğeler',
            'view-children' => 'Bunun altındaki :count kaydı göster',
        ],

        'erase' => [
            'btn'             => 'Kalıcı Olarak Sil',
            'confirm-title'   => 'Bu kayıt kalıcı olarak silinsin mi?',
            'confirm-message' => 'Bu kaydın ve onunla birleştirilen kopyaların tüm değerleri, etiketi ve kodu; geçmişleri, değişiklik talepleri ve kalite sorunlarıyla birlikte kaldırılır. Kaynak kimlikleri ve bağlantıları çözümlenmeye devam etsin diye kayıt, silinmiş bir yer tutucu olarak kalır. Bu işlem geri alınamaz.',
            'success'         => 'Kayıt kalıcı olarak silindi.',
            'not-retired'     => 'Yalnızca kullanımdan kaldırılmış bir kayıt silinebilir. Önce kullanımdan kaldırın.',
        ],

        'mass-retire' => [
            'success' => ':count kayıt kullanımdan kaldırıldı.',
            'pending' => ':count kullanımdan kaldırma onaya gönderildi.',
            'skipped' => ':count kayıt taslak veya etkin olmadığı ya da başka biri tarafından değiştirildiği için atlandı.',
            'none'    => 'Seçilen kayıtların hiçbiri kullanımdan kaldırılamadı.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Onay gerekiyor: değişiklik talebi #:id',
            'description'           => ':requester, :type üzerinde ":operation" değişikliğini onayınıza gönderdi.',
            'description-automated' => ':type üzerindeki otomatik ":operation" değişikliği onayınızı bekliyor.',
        ],
    ],

    'access' => [
        'menu'           => 'Veri Erişimi',
        'acl'            => [
            'index'  => 'Veri Erişimi',
            'create' => 'Oluştur',
            'edit'   => 'Düzenle',
            'delete' => 'Sil',
        ],
        'principals' => [
            'role' => 'Rol',
            'user' => 'Kullanıcı',
        ],
        'actions' => [
            'view'    => 'Görüntüle',
            'edit'    => 'Düzenle',
            'approve' => 'Onayla',
        ],
        'index' => [
            'title'      => 'Veri Erişimi',
            'create-btn' => 'İzin Ekle',
            'info'       => 'İzinler, her rolün veya kullanıcının kapsamlı varlık türlerinin hangi kayıtlarına eriştiğini belirler: seçilen kayıt ve altındaki her şey. Kullanıcının rolünden gelen erişim ile kullanıcıya verilen izinlerden gelen erişim birleşir.',
        ],
        'create' => [
            'title' => 'Veri Erişimi İzni Ekle',
        ],
        'edit' => [
            'title' => 'Veri Erişimi İznini Düzenle',
        ],
        'form' => [
            'save-btn'         => 'İzni Kaydet',
            'principal'        => 'Verilen',
            'principal-info'   => 'Rol izni, o role sahip her kullanıcıyı kapsar. İstisna için tek bir kullanıcıya izin verin.',
            'principal-type'   => 'İzin Verilen',
            'role'             => 'Rol',
            'user'             => 'Kullanıcı',
            'scope'            => 'Kapsam',
            'scope-info'       => 'İzin, seçilen kaydı ve hiyerarşide altındaki her kaydı kapsar.',
            'root'             => 'Alt Ağaç Kökü',
            'root-info'        => 'Örneğin bir saha veya fabrika. Altındaki kayıtlar izni devralır.',
            'entity-type'      => 'Varlık Türü',
            'entity-type-info' => 'İzni tek bir kapsamlı varlık türüyle sınırlayın veya tüm kapsamlı türler için boş bırakın.',
            'any-entity-type'  => 'Tüm kapsamlı varlık türleri',
            'actions'          => 'İşlemler',
            'actions-info'     => 'Görüntüle kayıtları okur, Düzenle kökün altındaki kayıtları değiştirir, kullanımdan kaldırır, birleştirir ve oluşturur, Onayla bekletilen değişikliklere karar verir. Düzenle ve Onayla, Görüntüle\'yi içerir.',
        ],
        'datagrid' => [
            'id'                => 'Kimlik',
            'principal-type'    => 'Tür',
            'principal'         => 'Verilen',
            'missing-principal' => 'Silindi',
            'entity-type'       => 'Varlık Türü',
            'root'              => 'Alt Ağaç Kökü',
            'actions'           => 'İşlemler',
            'edit'              => 'Düzenle',
            'delete'            => 'Sil',
        ],
        'role-card' => [
            'title'  => 'Ana Veri Erişimi',
            'info'   => 'Bu role sahip kullanıcıların kapsamlı varlık türlerinin hangi kayıtlarına eriştiği. Her izin, kendi kök kaydını ve altındaki her şeyi kapsar.',
            'empty'  => 'Bu rolün henüz ana veri erişim izni yok.',
            'remove' => 'Kaldır',
            'add'    => 'İzin ekle',
        ],
        'entity-type' => [
            'is-scoped'      => 'Erişimi Alt Ağaca Göre Sınırla',
            'is-scoped-info' => 'Açıkken kullanıcılar yalnızca Veri Erişimi ekranında rollerine veya kendilerine verilen alt ağaçlar içindeki bu türün kayıtlarına erişir. Paylaşılan referans verileri için kapalı bırakın.',
        ],
        'validation' => [
            'record-not-found'    => 'Alt ağaç kökü olarak görebildiğiniz bir kayıt seçin.',
            'parent-out-of-scope' => 'Düzenleme erişiminiz olan bir alt ağaç içinde bir üst kayıt seçin.',
        ],
        'create-success' => 'Veri erişimi izni eklendi.',
        'update-success' => 'Veri erişimi izni güncellendi.',
        'delete-success' => 'Veri erişimi izni silindi.',
    ],

    'install' => [
        'migrating' => 'Ana veri geçişleri çalıştırılıyor.',
        'demo'      => 'Üretim demo verileri yükleniyor.',
        'done'      => 'Ana veri yönetimi yüklendi. Etkin modüller: :modules.',
    ],

    'templates' => [
        'install-btn'     => 'Şablon yükle',
        'title'           => 'Alan şablonu yükle',
        'info'            => 'Müşteriler veya tedarikçiler gibi bir iş alanının varlık türlerini, alanlarını ve ortak kod listelerini ekler. Var olan her şey korunur; bu nedenle bir şablonu yeniden yüklemek hiçbir şeyi değiştirmez.',
        'template'        => 'Şablon',
        'label'           => 'Ad',
        'select-template' => 'Bir şablon seçin',
        'with-samples'    => 'Örnek kayıtlar ekle',
        'save-btn'        => 'Yükle',
        'installed'       => ':name şablonu yüklendi: :types varlık türü, :fields alan, :lists kod listesi ve :records örnek kayıt eklendi.',
        'choose'          => 'En az bir şablon belirtin veya --all kullanın. Kullanılabilir şablonlar: :names.',
    ],
];
