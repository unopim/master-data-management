<?php

return [
    'menu' => [
        'master-data' => 'Stamdata',
        'icons'       => 'Ikoner',
    ],

    'acl' => [
        'master-data' => 'Stamdata',
        'icons'       => [
            'index'  => 'Ikoner',
            'create' => 'Opret',
            'edit'   => 'Rediger',
            'delete' => 'Slet',
        ],

        'records' => [
            'erase' => 'Slet permanent',
        ],
    ],

    'dashboard' => [
        'title'      => 'Stamdata',
        'inside'     => 'Under: :types',
        'top-level'  => 'Øverste niveau',
        'standalone' => 'Selvstændig',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Stamdata',
            'records'     => 'Poster',
            'upsert'      => 'Opret eller opdater efter kildesystem-ID',
        ],

        'validation-failed'     => 'Valideringen mislykkedes.',
        'change-held'           => 'Ændringen afventer godkendelse.',
        'xref-not-found'        => 'Ingen post matcher dette eksterne id.',
        'entity-type-not-found' => 'Enhedstypen blev ikke fundet.',
        'record-not-found'      => 'Posten blev ikke fundet.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Enhedstype',
            'source-system'       => 'Kildesystem',
            'code-list'           => 'Kodeliste',
            'entity-type-info'    => 'Den enhedstype, hvis poster filen indeholder.',
            'source-system-info'  => 'Det system, filen kommer fra. Dets eksterne id\'er læses fra kolonnen external_id.',
            'code-list-info'      => 'Den liste, der importeres til. Koderne skrives til dens kladdeversion, som du udgiver separat.',
            'mapping-info'        => 'Én linje pr. omdøbt kolonne: filkolonne=feltkode, eller feltkode-sprog for et felt pr. sprog, f.eks. Asset Num=external_id og Description=name. Kolonner uden en linje læses under deres eget navn.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Vælg en aktiv enhedstype, hvis poster du må oprette eller redigere.',
            'source-system-invalid'              => 'Vælg et aktivt kildesystem.',
            'code-list-invalid'                  => 'Vælg en eksisterende kodeliste, som du må redigere.',
            'external-id-without-source-system'  => 'Filen har en kolonne med eksterne id\'er (:column). Vælg det kildesystem, den kommer fra, eller fjern kolonnen.',
        ],

        'schedule' => [
            'job-not-found'  => 'Intet importjob har koden :code.',
            'not-masterdata' => 'Jobbet :code er ikke en stamdataimport.',
            'no-user'        => 'Angiv den administrator, importen kører som, med --user eller MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Ingen aktiv administrator har e-mailen :email.',
            'file-not-found' => 'Filen :path blev ikke fundet på disken :disk.',
            'nothing'        => 'Ingen fil venter i :path på disken :disk.',
            'queued'         => 'Satte :file i kø til jobbet :code, sporet som #:id.',
            'imported'       => 'Importerede :file til jobbet :code, sporet som #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Produktionsdemoen er klar: :types enhedstyper, :systems kildesystemer, :records poster i et lokationstræ, :xrefs krydsreferencer, :lists kodelister, :fields felter, :aliases aliasser.',
        'invalid-template' => 'Skabelonen :name kunne ikke læses, eller har en formatversion, der ikke understøttes.',
    ],

    'platform-module' => [
        'name' => 'Stamdata (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Entitetstyper slettet: :count.',
        'mass-delete-skipped' => 'Ikke slettet, fordi de stadig har poster: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Overordnede typer',
            'records-count'        => 'Poster',
            'fields-count'         => 'Felter',
            'updated-at'           => 'Opdateret',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sorteringsrækkefølge',
        ],

        'edit' => [
            'schema-version' => 'Skema v:version',
        ],

        'form' => [
            'basics'                    => 'Grundlæggende',
            'identity'                  => 'Identitet',
            'hierarchy'                 => 'Hierarki',
            'governance'                => 'Styring',
            'quality-matching'          => 'Kvalitet og matchning',
            'matching-info'             => 'Indgående rækker fra kildesystemer matches med eksisterende poster i matchindbakken.',
            'auto-match-threshold'      => 'Tærskel for automatisk match',
            'auto-match-off'            => 'Fra',
            'auto-match-threshold-info' => 'Lad feltet være tomt for at gennemgå hver række. Fra 0,5 til 1: en række, hvis bedste forslag scorer mindst dette, uden at andre forslag scorer lige så højt, accepteres automatisk. Typer, der kræver godkendelse, holder stadig koblingen til godkendelse.',
            'match-on-write'            => 'Søg efter dubletter ved gem',
            'match-on-write-info'       => 'Hver gemt post sammenlignes i baggrunden med de øvrige poster ud fra matchreglerne. Slå fra for kun at finde dubletter i kvalitetsscanninger.',
            'manage-icons'              => 'Administrer ikoner',
            'sort-order'                => 'Sorteringsrækkefølge',
            'sort-order-info'           => 'Lavere tal vises først, fra 0 til 9999; typer med samme tal sorteres efter kode.',
        ],

        'quick-create' => [
            'code-info'             => 'Små bogstaver, tal og understregninger, startende med et bogstav. Kan ikke ændres efter gemning.',
            'code-pattern-info'     => 'Brug {seq} eller {seq:6} til løbenummeret og {TYPE} til typekoden, f.eks. {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Poster kommer fra flere kildesystemer og matches og flettes til én gylden post.',
                'centralized'   => 'Poster oprettes og vedligeholdes her og publiceres derefter til de systemer, der bruger dem.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Matchregel for :target',
        ],

        'show' => [
            'auto-matched' => 'Automatisk matchet',
            'auto-pending' => 'Matchet automatisk; koblingen afventer godkendelse.',
        ],

        'datagrid' => [
            'decision' => 'Beslutning',
            'auto'     => 'Automatisk matchet',
            'manual'   => 'Manuel',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Bruges af',
            'empty'        => 'Intet eksportjob udgiver poster af denne type og status.',
            'job'          => 'Eksportjob',
            'format'       => 'Format',
            'mode'         => 'Tilstand',
            'full'         => 'Fuld',
            'delta'        => 'Kun ændringer',
            'last-run-at'  => 'Seneste fuldførte kørsel',
            'no-run'       => 'Aldrig kørt',
            'record-state' => 'Denne post',
            'states'       => [
                'never_run'  => 'Ikke eksporteret endnu',
                'pending'    => 'Ændret siden seneste kørsel',
                'included'   => 'Med i seneste kørsel',
                'up_to_date' => 'Opdateret',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikonet blev oprettet.',
        'update-success' => 'Ikonet blev opdateret.',
        'delete-success' => 'Ikonet blev slettet.',
        'delete-in-use'  => '{1} Dette ikon bruges af én enhedstype og kan ikke slettes.|[2,*] Dette ikon bruges af :count enhedstyper og kan ikke slettes.',
        'index'          => [
            'title'      => 'Ikoner',
            'create-btn' => 'Tilføj ikon',
        ],

        'create' => [
            'title'    => 'Tilføj ikon',
            'save-btn' => 'Gem ikon',
        ],

        'edit' => [
            'title'    => 'Rediger ikon',
            'save-btn' => 'Gem ikon',
        ],

        'form' => [
            'code'      => 'Kode',
            'name'      => 'Navn',
            'file'      => 'Ikonfil',
            'file-info' => 'SVG, PNG eller WebP, op til :size KB. Brug én form på en gennemsigtig baggrund: den overtager temafarven.',
            'preview'   => 'Forhåndsvisning',
            'usage'     => '{0} Ingen enhedstype bruger endnu dette ikon.|{1} Bruges af én enhedstype.|[2,*] Bruges af :count enhedstyper.',
        ],

        'library' => [
            'barcode'    => 'Stregkode',
            'boxes'      => 'Materialer',
            'briefcase'  => 'Leverandør',
            'building_2' => 'Virksomhed',
            'cpu'        => 'Aktiv',
            'factory'    => 'Fabrik',
            'file_text'  => 'Kontrakt',
            'globe'      => 'Land',
            'handshake'  => 'Partner',
            'landmark'   => 'Juridisk enhed',
            'map_pin'    => 'Lokation',
            'network'    => 'Hierarki',
            'package'    => 'Produkt',
            'store'      => 'Butik',
            'tag'        => 'Kategori',
            'truck'      => 'Underleverandør',
            'user_round' => 'Kontakt',
            'users'      => 'Kunde',
            'warehouse'  => 'Lager',
            'wrench'     => 'Udstyr',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikon',
            'code'    => 'Kode',
            'name'    => 'Navn',
            'usage'   => 'Brugt af',
            'edit'    => 'Rediger',
            'delete'  => 'Slet',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Matchning',
        'title'          => 'Matchregler',
        'info'           => 'Bestem, hvordan poster af denne type sammenlignes for at finde dubletter og matche indgående rækker. Uden regler sammenlignes koden og etiketten.',
        'create-btn'     => 'Tilføj matchregel',
        'create-success' => 'Matchreglen blev oprettet.',
        'update-success' => 'Matchreglen blev opdateret.',
        'delete-success' => 'Matchreglen blev slettet.',

        'create' => [
            'title' => 'Tilføj matchregel',
        ],

        'edit' => [
            'title' => 'Rediger matchregel',
        ],

        'form' => [
            'target'            => 'Sammenlign',
            'select-target'     => 'Vælg, hvad der skal sammenlignes',
            'target-info'       => 'Postens kode, dens etiket eller et felt i denne enhedstype.',
            'comparator'        => 'Sammenligningsmetode',
            'select-comparator' => 'Vælg en sammenligningsmetode',
            'comparator-info'   => 'Hvordan de to værdier sammenlignes, f.eks. Uskarp til navne med stavefejl eller Numerisk til beløb.',
            'weight'            => 'Vægt',
            'weight-info'       => 'Hvor meget denne regel tæller, fra 0,1 til 100. Scoren er det vægtede gennemsnit af de regler, som den indgående række har en værdi for.',
            'tolerance'         => 'Tolerance',
            'tolerance-info'    => 'Den største forskel mellem to tal, der stadig regnes som ens. Lad feltet være tomt for kun at matche ens tal.',
            'days'              => 'Datointerval (dage)',
            'days-info'         => 'Det største antal dage, to datoer må ligge fra hinanden og stadig regnes som ens. Lad feltet være tomt for kun at matche samme dag.',
            'sort-order'        => 'Sorteringsrækkefølge',
            'is-blocking'       => 'Blokeringsnøgle',
            'is-blocking-info'  => 'Slå også de poster op, der har præcis denne værdi, selv når deres kode og etiket ser forskellige ud. Felter skal være søgbare.',
            'save-btn'          => 'Gem matchregel',
        ],

        'targets' => [
            'code'  => 'Kode',
            'label' => 'Etiket',
        ],

        'comparators' => [
            'exact'    => 'Præcis',
            'fuzzy'    => 'Uskarp',
            'token'    => 'Samme ord',
            'phonetic' => 'Lyder ens',
            'numeric'  => 'Numerisk',
            'date'     => 'Dato',
        ],

        'datagrid' => [
            'target'     => 'Sammenlign',
            'comparator' => 'Sammenligningsmetode',
            'weight'     => 'Vægt',
            'blocking'   => 'Blokeringsnøgle',
            'sort-order' => 'Sorteringsrækkefølge',
            'yes'        => 'Ja',
            'no'         => 'Nej',
            'edit'       => 'Rediger',
            'delete'     => 'Slet',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Kun koden, etiketten eller et søgbart felt kan være en blokeringsnøgle.',
            'too-many'                => 'En enhedstype kan højst have :count matchregler.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Overlevelse',
        'title'          => 'Overlevelsesregler',
        'info'           => 'Bestem, hvilken værdi golden record beholder, når to poster af denne type flettes. Uden regler beholder den overlevende post sin værdi, medmindre den er tom.',
        'create-btn'     => 'Tilføj overlevelsesregel',
        'create-success' => 'Overlevelsesregel oprettet.',
        'update-success' => 'Overlevelsesregel opdateret.',
        'delete-success' => 'Overlevelsesregel slettet.',

        'create' => [
            'title' => 'Tilføj overlevelsesregel',
        ],

        'edit' => [
            'title' => 'Rediger overlevelsesregel',
        ],

        'form' => [
            'target'            => 'Gælder for',
            'select-target'     => 'Vælg, hvad reglen gælder for',
            'target-info'       => 'Et felt, postens etiket eller alle felter uden egen regel.',
            'strategy'          => 'Strategi',
            'select-strategy'   => 'Vælg en strategi',
            'strategy-info'     => 'Hvordan den overlevende værdi vælges. Dataansvarlige kan stadig ændre hvert valg på fletteskærmen.',
            'source-order'      => 'Kilderækkefølge',
            'select-sources'    => 'Vælg kildesystemer',
            'source-order-info' => 'Det mest troværdige kildesystem først. Udeladte systemer følger deres prioritet. Lad feltet stå tomt for kun at bruge kildesystemernes prioritet.',
            'sort-order'        => 'Sorteringsrækkefølge',
            'save-btn'          => 'Gem overlevelsesregel',
        ],

        'targets' => [
            'default' => 'Standard for alle felter',
            'label'   => 'Etiket',
        ],

        'strategies' => [
            'source_priority' => 'Mest troværdige kilde',
            'most_recent'     => 'Senest opdateret',
            'most_complete'   => 'Mest komplet',
            'keep_target'     => 'Behold den overlevende',
            'manual'          => 'Dataansvarlig beslutter',
        ],

        'datagrid' => [
            'target'     => 'Gælder for',
            'strategy'   => 'Strategi',
            'sort-order' => 'Sorteringsrækkefølge',
            'edit'       => 'Rediger',
            'delete'     => 'Slet',
        ],

        'validation' => [
            'target-taken' => 'Denne enhedstype har allerede en overlevelsesregel for dette mål.',
            'too-many'     => 'En enhedstype kan højst have :count overlevelsesregler.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Gennemse :type herunder',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Øverste niveau',
        ],

        'datagrid' => [
            'erase'         => 'Slet permanent',
            'parent'        => 'Overordnet',
            'top-level'     => 'Øverste niveau',
            'children'      => 'Underordnede',
            'view-children' => 'Vis de :count poster under denne',
        ],

        'erase' => [
            'btn'             => 'Slet permanent',
            'confirm-title'   => 'Slet denne post permanent?',
            'confirm-message' => 'Alle værdier, etiketten og koden for denne post og for de dubletter, der er flettet ind i den, fjernes sammen med deres historik, ændringsanmodninger og kvalitetsproblemer. Posten bevares som en slettet pladsholder, så dens kilde-ID\'er og links stadig kan slås op. Dette kan ikke fortrydes.',
            'success'         => 'Posten er slettet permanent.',
            'not-retired'     => 'Kun en udfaset post kan slettes. Udfas den først.',
        ],

        'mass-retire' => [
            'success' => ':count post(er) udfaset.',
            'pending' => ':count udfasning(er) sendt til godkendelse.',
            'skipped' => ':count post(er) sprunget over, fordi de ikke er kladde eller aktive eller er ændret af en anden.',
            'none'    => 'Ingen af de valgte poster kunne udfases.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Godkendelse påkrævet: ændringsanmodning #:id',
            'description'           => ':requester har indsendt en ":operation"-ændring til :type til din godkendelse.',
            'description-automated' => 'En automatisk ":operation"-ændring til :type venter på din godkendelse.',
        ],
    ],

    'access' => [
        'menu'           => 'Dataadgang',
        'acl'            => [
            'index'  => 'Dataadgang',
            'create' => 'Opret',
            'edit'   => 'Rediger',
            'delete' => 'Slet',
        ],
        'principals' => [
            'role' => 'Rolle',
            'user' => 'Bruger',
        ],
        'actions' => [
            'view'    => 'Se',
            'edit'    => 'Rediger',
            'approve' => 'Godkend',
        ],
        'index' => [
            'title'      => 'Dataadgang',
            'create-btn' => 'Tilføj tildeling',
            'info'       => 'Tildelinger afgør, hvilke poster af afgrænsede enhedstyper hver rolle eller bruger har adgang til: den valgte post og alt under den. Adgang fra en brugers rolle og fra tildelinger givet til brugeren lægges sammen.',
        ],
        'create' => [
            'title' => 'Tilføj dataadgangstildeling',
        ],
        'edit' => [
            'title' => 'Rediger dataadgangstildeling',
        ],
        'form' => [
            'save-btn'         => 'Gem tildeling',
            'principal'        => 'Tildelt til',
            'principal-info'   => 'En rolletildeling gælder for alle brugere med den rolle. Tildel til en enkelt bruger som undtagelse.',
            'principal-type'   => 'Tildel til',
            'role'             => 'Rolle',
            'user'             => 'Bruger',
            'scope'            => 'Omfang',
            'scope-info'       => 'Tildelingen dækker den valgte post og alle poster under den i hierarkiet.',
            'root'             => 'Rod for undertræ',
            'root-info'        => 'For eksempel et site eller en fabrik. Poster under den arver tildelingen.',
            'entity-type'      => 'Enhedstype',
            'entity-type-info' => 'Begræns tildelingen til én afgrænset enhedstype, eller lad feltet stå tomt for alle afgrænsede typer.',
            'any-entity-type'  => 'Alle afgrænsede enhedstyper',
            'actions'          => 'Handlinger',
            'actions-info'     => 'Se læser poster, Rediger ændrer, udfaser, fletter og opretter poster under roden, Godkend afgør tilbageholdte ændringer. Rediger og Godkend omfatter Se.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Type',
            'principal'         => 'Tildelt til',
            'missing-principal' => 'Slettet',
            'entity-type'       => 'Enhedstype',
            'root'              => 'Rod for undertræ',
            'actions'           => 'Handlinger',
            'edit'              => 'Rediger',
            'delete'            => 'Slet',
        ],
        'role-card' => [
            'title'  => 'Adgang til stamdata',
            'info'   => 'Hvilke poster af afgrænsede enhedstyper brugere med denne rolle har adgang til. Hver tildeling dækker sin rodpost og alt under den.',
            'empty'  => 'Denne rolle har endnu ingen tildelinger af adgang til stamdata.',
            'remove' => 'Fjern',
            'add'    => 'Tilføj en tildeling',
        ],
        'entity-type' => [
            'is-scoped'      => 'Begræns adgang efter undertræ',
            'is-scoped-info' => 'Når den er slået til, har brugere kun adgang til poster af denne type inden for de undertræer, der er tildelt deres rolle eller dem selv på skærmbilledet Dataadgang. Lad den være slået fra for delte referencedata.',
        ],
        'validation' => [
            'record-not-found'    => 'Vælg en post, du kan se, som rod for undertræet.',
            'parent-out-of-scope' => 'Vælg en overordnet post i et undertræ, hvor du har redigeringsadgang.',
        ],
        'create-success' => 'Dataadgangstildelingen er tilføjet.',
        'update-success' => 'Dataadgangstildelingen er opdateret.',
        'delete-success' => 'Dataadgangstildelingen er slettet.',
    ],
];
