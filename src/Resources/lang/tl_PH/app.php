<?php

return [
    'menu' => [
        'master-data' => 'Master Data',
        'icons'       => 'Mga Icon',
    ],

    'acl' => [
        'master-data' => 'Master Data',
        'icons'       => [
            'index'  => 'Mga Icon',
            'create' => 'Lumikha',
            'edit'   => 'I-edit',
            'delete' => 'Burahin',
        ],

        'records' => [
            'erase' => 'Burahin nang Permanente',
        ],

        'entity-types' => [
            'templates' => 'Mag-install ng mga template',
        ],
    ],

    'dashboard' => [
        'title'      => 'Master Data',
        'inside'     => 'Nasa loob ng: :types',
        'top-level'  => 'Pinakamataas na antas',
        'standalone' => 'Hiwalay',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Master Data',
            'records'     => 'Mga Tala',
            'upsert'      => 'I-upsert ayon sa Source System ID',
        ],

        'validation-failed'     => 'Nabigo ang validation.',
        'change-held'           => 'Naghihintay ng pag-apruba ang pagbabago.',
        'xref-not-found'        => 'Walang talang tumutugma sa external id na ito.',
        'entity-type-not-found' => 'Hindi nahanap ang uri ng entity.',
        'record-not-found'      => 'Hindi nahanap ang tala.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Uri ng Entity',
            'source-system'       => 'Source System',
            'code-list'           => 'Code List',
            'entity-type-info'    => 'Ang uri ng entity na ang mga record ay nasa file.',
            'source-system-info'  => 'Ang system kung saan galing ang file. Binabasa ang mga external ID nito mula sa column na external_id.',
            'code-list-info'      => 'Ang listahang pag-iimportan. Isinusulat ang mga code sa draft na bersyon nito, na hiwalay mong ilalathala.',
            'mapping-info'        => 'Isang linya bawat pinalitang pangalan ng column: column ng file=code ng field, o code ng field-locale para sa field na bawat locale, hal. Asset Num=external_id at Description=name. Ang mga column na walang linya ay binabasa sa sarili nilang pangalan.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Pumili ng aktibong uri ng entity na ang mga record ay maaari mong gawin o i-edit.',
            'source-system-invalid'              => 'Pumili ng aktibong source system.',
            'code-list-invalid'                  => 'Pumili ng umiiral na code list na maaari mong i-edit.',
            'external-id-without-source-system'  => 'May column ng external ID ang file (:column). Piliin ang source system na pinanggalingan nito, o alisin ang column.',
        ],

        'schedule' => [
            'job-not-found'  => 'Walang import job na may code na :code.',
            'not-masterdata' => 'Ang job na :code ay hindi master data import.',
            'no-user'        => 'Pangalanan ang admin kung kanino tatakbo ang import gamit ang --user o MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Walang aktibong admin na may e-mail na :email.',
            'file-not-found' => 'Hindi nahanap ang file na :path sa disk na :disk.',
            'nothing'        => 'Walang file na naghihintay sa :path sa disk na :disk.',
            'queued'         => 'Inilagay sa queue ang :file para sa job na :code, sinusubaybayan bilang #:id.',
            'imported'       => 'Na-import ang :file para sa job na :code, sinusubaybayan bilang #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Handa na ang demo sa pagmamanupaktura: :types uri ng entity, :systems source system, :records record sa isang site tree, :xrefs cross-reference, :lists code list, :fields field, :aliases alias.',
        'invalid-template' => 'Hindi mabasa ang template na :name o hindi sinusuportahan ang bersyon ng format nito.',
    ],

    'platform-module' => [
        'name' => 'Master Data (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Mga uri ng entity na binura: :count.',
        'mass-delete-skipped' => 'Hindi binura dahil may mga record pa: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Mga Parent na Uri',
            'records-count'        => 'Mga Record',
            'fields-count'         => 'Mga Field',
            'updated-at'           => 'Na-update',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Pagkakasunod-sunod',
        ],

        'edit' => [
            'schema-version' => 'Iskema v:version',
        ],

        'form' => [
            'basics'                    => 'Mga Pangunahing Detalye',
            'identity'                  => 'Pagkakakilanlan',
            'hierarchy'                 => 'Hirarkiya',
            'governance'                => 'Pamamahala',
            'quality-matching'          => 'Kalidad at Pagtutugma',
            'matching-info'             => 'Ang mga papasok na hilera mula sa mga source system ay itinutugma sa mga umiiral na record sa match inbox.',
            'auto-match-threshold'      => 'Threshold ng Awtomatikong Pagtutugma',
            'auto-match-off'            => 'Naka-off',
            'auto-match-threshold-info' => 'Iwanang walang laman para suriin ang bawat hilera. Mula 0.5 hanggang 1: ang hilerang ang pinakamahusay na mungkahi ay umabot man lang sa halagang ito, at walang ibang mungkahing kasintaas, ay awtomatikong tatanggapin. Sa mga uri na nangangailangan ng pag-apruba, naghihintay pa rin ng pag-apruba ang link.',
            'match-on-write'            => 'Suriin ang mga Duplicate sa Pag-save',
            'match-on-write-info'       => 'Ang bawat na-save na tala ay inihahambing sa iba pang tala sa background gamit ang mga panuntunan sa pagtutugma. I-off para makahanap ng mga duplicate sa mga quality scan lamang.',
            'manage-icons'              => 'Pamahalaan ang mga icon',
            'sort-order'                => 'Pagkakasunod-sunod',
            'sort-order-info'           => 'Unang ipinapakita ang mas maliliit na numero, mula 0 hanggang 9999; ang mga uri na may parehong numero ay inaayos ayon sa code.',
        ],

        'quick-create' => [
            'code-info'             => 'Maliliit na titik, numero at underscore, na nagsisimula sa titik. Hindi na ito mababago pagkatapos i-save.',
            'code-pattern-info'     => 'Gamitin ang {seq} o {seq:6} para sa sunud-sunod na numero at {TYPE} para sa code ng uri, halimbawa {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Dumarating ang mga record mula sa ilang source system at itinutugma at pinagsasama sa iisang golden record.',
                'centralized'   => 'Dito ginagawa at pinapanatili ang mga record, saka inilalathala sa mga system na gumagamit sa mga ito.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Panuntunan sa pagtutugma sa :target',
        ],

        'show' => [
            'auto-matched' => 'Awtomatikong naitugma',
            'auto-pending' => 'Awtomatikong naitugma; naghihintay ng pag-apruba ang link.',
        ],

        'datagrid' => [
            'decision' => 'Desisyon',
            'auto'     => 'Awtomatikong naitugma',
            'manual'   => 'Manwal',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Ginagamit Ng',
            'empty'        => 'Walang export job na naglalathala ng mga record ng uring ito at status.',
            'job'          => 'Export Job',
            'format'       => 'Format',
            'mode'         => 'Mode',
            'full'         => 'Buo',
            'delta'        => 'Mga pagbabago lamang',
            'last-run-at'  => 'Huling Natapos na Pagtakbo',
            'no-run'       => 'Hindi pa tumakbo',
            'record-state' => 'Ang Record na Ito',
            'states'       => [
                'never_run'  => 'Hindi pa na-export',
                'pending'    => 'Nagbago mula sa huling pagtakbo',
                'included'   => 'Kasama sa huling pagtakbo',
                'up_to_date' => 'Napapanahon',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Matagumpay na nalikha ang icon.',
        'update-success' => 'Matagumpay na na-update ang icon.',
        'delete-success' => 'Matagumpay na nabura ang icon.',
        'delete-in-use'  => '{1} Ang icon na ito ay ipinapakita ng isang uri ng entity at hindi maaaring burahin.|[2,*] Ang icon na ito ay ipinapakita ng :count uri ng entity at hindi maaaring burahin.',
        'index'          => [
            'title'      => 'Mga Icon',
            'create-btn' => 'Magdagdag ng Icon',
        ],

        'create' => [
            'title'    => 'Magdagdag ng Icon',
            'save-btn' => 'I-save ang Icon',
        ],

        'edit' => [
            'title'    => 'I-edit ang Icon',
            'save-btn' => 'I-save ang Icon',
        ],

        'form' => [
            'code'      => 'Code',
            'name'      => 'Pangalan',
            'file'      => 'File ng Icon',
            'file-info' => 'SVG, PNG o WebP, hanggang :size KB. Gumamit ng isang hugis sa transparent na background: kukunin nito ang kulay ng tema.',
            'preview'   => 'Preview',
            'usage'     => '{0} Wala pang uri ng entity na gumagamit ng icon na ito.|{1} Ginagamit ng isang uri ng entity.|[2,*] Ginagamit ng :count uri ng entity.',
        ],

        'library' => [
            'barcode'    => 'Barcode',
            'boxes'      => 'Mga materyales',
            'briefcase'  => 'Vendor',
            'building_2' => 'Kumpanya',
            'cpu'        => 'Asset',
            'factory'    => 'Planta',
            'file_text'  => 'Kontrata',
            'globe'      => 'Bansa',
            'handshake'  => 'Kasosyo',
            'landmark'   => 'Legal na entidad',
            'map_pin'    => 'Lokasyon',
            'network'    => 'Hierarkiya',
            'package'    => 'Produkto',
            'store'      => 'Tindahan',
            'tag'        => 'Kategorya',
            'truck'      => 'Supplier',
            'user_round' => 'Contact',
            'users'      => 'Customer',
            'warehouse'  => 'Bodega',
            'wrench'     => 'Kagamitan',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icon',
            'code'    => 'Code',
            'name'    => 'Pangalan',
            'usage'   => 'Ginagamit ng',
            'edit'    => 'I-edit',
            'delete'  => 'Burahin',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Pagtutugma',
        'title'          => 'Mga Panuntunan sa Pagtutugma',
        'info'           => 'Itakda kung paano pinaghahambing ang mga tala ng uring ito para makahanap ng mga duplicate at maitugma ang mga papasok na hilera. Kapag walang panuntunan, ang code at label ang pinaghahambing.',
        'create-btn'     => 'Magdagdag ng Panuntunan sa Pagtutugma',
        'create-success' => 'Matagumpay na nalikha ang panuntunan sa pagtutugma.',
        'update-success' => 'Matagumpay na na-update ang panuntunan sa pagtutugma.',
        'delete-success' => 'Matagumpay na nabura ang panuntunan sa pagtutugma.',

        'create' => [
            'title' => 'Magdagdag ng Panuntunan sa Pagtutugma',
        ],

        'edit' => [
            'title' => 'I-edit ang Panuntunan sa Pagtutugma',
        ],

        'form' => [
            'target'            => 'Ihambing',
            'select-target'     => 'Piliin kung ano ang ihahambing',
            'target-info'       => 'Ang code ng tala, ang label nito, o isang field ng uri ng entity na ito.',
            'comparator'        => 'Paraan ng paghahambing',
            'select-comparator' => 'Pumili ng paraan ng paghahambing',
            'comparator-info'   => 'Kung paano pinaghahambing ang dalawang halaga, hal. Malapit para sa mga pangalang may typo o Numeriko para sa mga halaga ng pera.',
            'weight'            => 'Bigat',
            'weight-info'       => 'Gaano kalaki ang bilang ng panuntunang ito, mula 0.1 hanggang 100. Ang iskor ay ang weighted average ng mga panuntunang may halaga ang papasok na hilera.',
            'tolerance'         => 'Palugit',
            'tolerance-info'    => 'Ang pinakamalaking pagitan ng dalawang numero na itinuturing pa ring magkahawig. Iwanang walang laman para itugma lamang ang magkaparehong numero.',
            'days'              => 'Saklaw ng Petsa (araw)',
            'days-info'         => 'Ang pinakamaraming araw na maaaring pagitan ng dalawang petsa para maituring pa ring magkahawig. Iwanang walang laman para itugma lamang ang parehong araw.',
            'sort-order'        => 'Pagkakasunod-sunod',
            'is-blocking'       => 'Blocking Key',
            'is-blocking-info'  => 'Hanapin din ang mga tala na may eksaktong ganitong halaga, kahit magkaiba ang hitsura ng kanilang code at label. Dapat nahahanap ang mga field.',
            'save-btn'          => 'I-save ang Panuntunan sa Pagtutugma',
        ],

        'targets' => [
            'code'  => 'Code',
            'label' => 'Label',
        ],

        'comparators' => [
            'exact'    => 'Eksakto',
            'fuzzy'    => 'Malapit',
            'token'    => 'Parehong salita',
            'phonetic' => 'Magkatunog',
            'numeric'  => 'Numeriko',
            'date'     => 'Petsa',
        ],

        'datagrid' => [
            'target'     => 'Ihambing',
            'comparator' => 'Paraan ng paghahambing',
            'weight'     => 'Bigat',
            'blocking'   => 'Blocking Key',
            'sort-order' => 'Pagkakasunod-sunod',
            'yes'        => 'Oo',
            'no'         => 'Hindi',
            'edit'       => 'I-edit',
            'delete'     => 'Burahin',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Ang code, ang label, o isang nahahanap na field lamang ang maaaring maging blocking key.',
            'too-many'                => 'Ang isang uri ng entity ay maaaring magkaroon ng hanggang :count panuntunan sa pagtutugma.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Survivorship',
        'title'          => 'Mga Panuntunan sa Survivorship',
        'info'           => 'Tukuyin kung aling value ang pananatilihin ng golden record kapag pinagsama ang dalawang record ng ganitong uri. Kapag walang panuntunan, pinananatili ng natirang record ang value nito maliban kung walang laman.',
        'create-btn'     => 'Magdagdag ng Panuntunan sa Survivorship',
        'create-success' => 'Matagumpay na nagawa ang panuntunan sa survivorship.',
        'update-success' => 'Matagumpay na na-update ang panuntunan sa survivorship.',
        'delete-success' => 'Matagumpay na nabura ang panuntunan sa survivorship.',

        'create' => [
            'title' => 'Magdagdag ng Panuntunan sa Survivorship',
        ],

        'edit' => [
            'title' => 'I-edit ang Panuntunan sa Survivorship',
        ],

        'form' => [
            'target'            => 'Ina-apply sa',
            'select-target'     => 'Piliin kung saan ia-apply ang panuntunan',
            'target-info'       => 'Isang field, ang label ng record, o bawat field na walang sariling panuntunan.',
            'strategy'          => 'Estratehiya',
            'select-strategy'   => 'Pumili ng estratehiya',
            'strategy-info'     => 'Paano pinipili ang natirang value. Maaari pa ring baguhin ng mga steward ang bawat pinili sa merge screen.',
            'source-order'      => 'Pagkakasunod-sunod ng Source',
            'select-sources'    => 'Pumili ng mga source system',
            'source-order-info' => 'Una ang pinakapinagkakatiwalaang source system. Ang mga hindi nabanggit ay susunod sa kanilang priyoridad. Iwanang walang laman para gamitin lang ang priyoridad ng source system.',
            'sort-order'        => 'Pagkakasunod-sunod',
            'save-btn'          => 'I-save ang Panuntunan sa Survivorship',
        ],

        'targets' => [
            'default' => 'Default para sa lahat ng field',
            'label'   => 'Label',
        ],

        'strategies' => [
            'source_priority' => 'Pinakapinagkakatiwalaang source',
            'most_recent'     => 'Pinakahuling na-update',
            'most_complete'   => 'Pinakakumpleto',
            'keep_target'     => 'Panatilihin ang natira',
            'manual'          => 'Ang steward ang magpapasya',
        ],

        'datagrid' => [
            'target'     => 'Ina-apply sa',
            'strategy'   => 'Estratehiya',
            'sort-order' => 'Pagkakasunod-sunod',
            'edit'       => 'I-edit',
            'delete'     => 'Burahin',
        ],

        'validation' => [
            'target-taken' => 'May panuntunan na sa survivorship ang uri ng entity na ito para sa target na ito.',
            'too-many'     => 'Ang isang uri ng entity ay maaaring magkaroon ng hanggang :count panuntunan sa survivorship.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'I-browse ang :type sa ilalim',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Pinakamataas na Antas',
        ],

        'datagrid' => [
            'erase'         => 'Burahin nang Permanente',
            'parent'        => 'Magulang',
            'top-level'     => 'Pinakamataas na antas',
            'children'      => 'Mga anak',
            'view-children' => 'Ipakita ang :count na record sa ilalim nito',
        ],

        'erase' => [
            'btn'             => 'Burahin nang Permanente',
            'confirm-title'   => 'Burahin nang permanente ang record na ito?',
            'confirm-message' => 'Aalisin ang lahat ng value, label at code ng record na ito at ng mga duplicate na pinagsama rito, kasama ang kasaysayan, mga change request at mga isyu sa kalidad. Mananatili ang record bilang binurang placeholder para patuloy na gumana ang mga source ID at link nito. Hindi na ito maibabalik.',
            'success'         => 'Permanenteng nabura ang record.',
            'not-retired'     => 'Retiradong record lang ang maaaring burahin. I-retire muna ito.',
        ],

        'mass-retire' => [
            'success' => ':count record ang na-retire.',
            'pending' => ':count retirement ang isinumite para sa pag-apruba.',
            'skipped' => ':count record ang nilaktawan dahil hindi draft o aktibo ang mga ito, o binago ng ibang tao.',
            'none'    => 'Wala sa mga napiling record ang na-retire.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Kailangan ng pag-apruba: kahilingan sa pagbabago #:id',
            'description'           => 'Nagsumite si :requester ng ":operation" na pagbabago sa :type para sa iyong pag-apruba.',
            'description-automated' => 'Isang awtomatikong ":operation" na pagbabago sa :type ang naghihintay ng iyong pag-apruba.',
        ],
    ],

    'access' => [
        'menu'           => 'Access sa Data',
        'acl'            => [
            'index'  => 'Access sa Data',
            'create' => 'Gumawa',
            'edit'   => 'I-edit',
            'delete' => 'Burahin',
        ],
        'principals' => [
            'role' => 'Tungkulin',
            'user' => 'User',
        ],
        'actions' => [
            'view'    => 'Tingnan',
            'edit'    => 'I-edit',
            'approve' => 'Aprubahan',
        ],
        'index' => [
            'title'      => 'Access sa Data',
            'create-btn' => 'Magdagdag ng Pahintulot',
            'info'       => 'Tinutukoy ng mga pahintulot kung aling mga record ng mga scoped na uri ng entity ang naaabot ng bawat tungkulin o user: ang napiling record at lahat ng nasa ilalim nito. Nagsasama ang access mula sa tungkulin ng user at mula sa mga pahintulot na ibinigay sa user.',
        ],
        'create' => [
            'title' => 'Magdagdag ng Pahintulot sa Access sa Data',
        ],
        'edit' => [
            'title' => 'I-edit ang Pahintulot sa Access sa Data',
        ],
        'form' => [
            'save-btn'         => 'I-save ang Pahintulot',
            'principal'        => 'Ibinigay Kay',
            'principal-info'   => 'Naaabot ng pahintulot ng tungkulin ang bawat user na may tungkuling iyon. Ibigay sa isang user para sa eksepsyon.',
            'principal-type'   => 'Ibigay Kay',
            'role'             => 'Tungkulin',
            'user'             => 'User',
            'scope'            => 'Saklaw',
            'scope-info'       => 'Sakop ng pahintulot ang napiling record at bawat record sa ilalim nito sa hierarchy.',
            'root'             => 'Ugat ng Subtree',
            'root-info'        => 'Halimbawa, isang site o planta. Namamana ng mga record sa ilalim nito ang pahintulot.',
            'entity-type'      => 'Uri ng Entity',
            'entity-type-info' => 'Limitahan ang pahintulot sa isang scoped na uri ng entity, o iwanang walang laman para sa lahat ng scoped na uri.',
            'any-entity-type'  => 'Lahat ng scoped na uri ng entity',
            'actions'          => 'Mga Aksyon',
            'actions-info'     => 'Binabasa ng Tingnan ang mga record, binabago, inire-retiro, pinagsasama, at lumilikha ng mga record sa ilalim ng ugat ang I-edit, nagpapasya sa mga nakabinbing pagbabago ang Aprubahan. Kasama ang Tingnan sa I-edit at Aprubahan.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Uri',
            'principal'         => 'Ibinigay Kay',
            'missing-principal' => 'Binura',
            'entity-type'       => 'Uri ng Entity',
            'root'              => 'Ugat ng Subtree',
            'actions'           => 'Mga Aksyon',
            'edit'              => 'I-edit',
            'delete'            => 'Burahin',
        ],
        'role-card' => [
            'title'  => 'Access sa Master Data',
            'info'   => 'Aling mga record ng mga scoped na uri ng entity ang naaabot ng mga user na may tungkuling ito. Sakop ng bawat pahintulot ang ugat nitong record at lahat ng nasa ilalim nito.',
            'empty'  => 'Wala pang pahintulot sa access sa master data ang tungkuling ito.',
            'remove' => 'Alisin',
            'add'    => 'Magdagdag ng pahintulot',
        ],
        'entity-type' => [
            'is-scoped'      => 'Limitahan ang Access ayon sa Subtree',
            'is-scoped-info' => 'Kapag naka-on, ang mga user ay makaaabot lang sa mga record ng uri na ito sa loob ng mga subtree na ibinigay sa kanilang tungkulin o sa kanila sa screen ng Access sa Data. Iwanang naka-off para sa nakabahaging reference data.',
        ],
        'validation' => [
            'record-not-found'    => 'Pumili ng record na nakikita mo bilang ugat ng subtree.',
            'parent-out-of-scope' => 'Pumili ng parent sa loob ng subtree kung saan may access ka sa pag-edit.',
        ],
        'create-success' => 'Naidagdag ang pahintulot sa access sa data.',
        'update-success' => 'Na-update ang pahintulot sa access sa data.',
        'delete-success' => 'Nabura ang pahintulot sa access sa data.',
    ],

    'install' => [
        'migrating' => 'Pinapatakbo ang mga migration ng master data.',
        'demo'      => 'Ini-install ang demo data ng manufacturing.',
        'done'      => 'Naka-install na ang master data management. Mga aktibong module: :modules.',
    ],

    'templates' => [
        'install-btn'     => 'Mag-install ng template',
        'title'           => 'Mag-install ng template ng domain',
        'info'            => 'Idinadagdag ang mga uri ng entity, field at pinagsasaluhang listahan ng code ng isang domain ng negosyo, gaya ng mga kustomer o tagapagtustos. Pinananatili ang anumang mayroon na, kaya walang nagbabago kapag muling in-install ang template.',
        'template'        => 'Template',
        'label'           => 'Pangalan',
        'select-template' => 'Pumili ng template',
        'with-samples'    => 'Magdagdag ng mga halimbawang record',
        'save-btn'        => 'I-install',
        'installed'       => 'Na-install ang template na :name: nadagdag ang :types uri ng entity, :fields field, :lists listahan ng code at :records halimbawang record.',
        'choose'          => 'Magbigay ng kahit isang template o gamitin ang --all. Mga available na template: :names.',
    ],
];
