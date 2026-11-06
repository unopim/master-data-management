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
            'create' => 'Opprett',
            'edit'   => 'Rediger',
            'delete' => 'Slett',
        ],

        'records' => [
            'erase' => 'Slett permanent',
        ],
    ],

    'dashboard' => [
        'title'      => 'Stamdata',
        'inside'     => 'Under: :types',
        'top-level'  => 'Øverste nivå',
        'standalone' => 'Frittstående',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Stamdata',
            'records'     => 'Poster',
            'upsert'      => 'Opprett eller oppdater etter kildesystem-ID',
        ],

        'validation-failed'     => 'Valideringen mislyktes.',
        'change-held'           => 'Endringen venter på godkjenning.',
        'xref-not-found'        => 'Ingen post samsvarer med denne eksterne ID-en.',
        'entity-type-not-found' => 'Enhetstypen ble ikke funnet.',
        'record-not-found'      => 'Posten ble ikke funnet.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Enhetstype',
            'source-system'       => 'Kildesystem',
            'code-list'           => 'Kodeliste',
            'entity-type-info'    => 'Enhetstypen som filen inneholder poster for.',
            'source-system-info'  => 'Systemet filen kommer fra. De eksterne ID-ene leses fra kolonnen external_id.',
            'code-list-info'      => 'Listen det importeres til. Kodene skrives til utkastversjonen, som du publiserer separat.',
            'mapping-info'        => 'Én linje per omdøpt kolonne: filkolonne=feltkode, eller feltkode-språk for et felt per språk, f.eks. Asset Num=external_id og Description=name. Kolonner uten linje leses under sitt eget navn.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Velg en aktiv enhetstype du kan opprette eller redigere poster for.',
            'source-system-invalid'              => 'Velg et aktivt kildesystem.',
            'code-list-invalid'                  => 'Velg en eksisterende kodeliste du kan redigere.',
            'external-id-without-source-system'  => 'Filen har en kolonne med eksterne ID-er (:column). Velg kildesystemet den kommer fra, eller fjern kolonnen.',
        ],

        'schedule' => [
            'job-not-found'  => 'Ingen importjobb har koden :code.',
            'not-masterdata' => 'Jobben :code er ikke en stamdataimport.',
            'no-user'        => 'Oppgi administratoren importen kjøres som, med --user eller MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Ingen aktiv administrator har e-posten :email.',
            'file-not-found' => 'Filen :path ble ikke funnet på disken :disk.',
            'nothing'        => 'Ingen fil venter i :path på disken :disk.',
            'queued'         => 'Satte :file i kø for jobben :code, sporet som #:id.',
            'imported'       => 'Importerte :file for jobben :code, sporet som #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Produksjonsdemoen er klar: :types enhetstyper, :systems kildesystemer, :records poster i et lokasjonstre, :xrefs kryssreferanser, :lists kodelister, :fields felt, :aliases aliaser.',
        'invalid-template' => 'Malen :name kunne ikke leses, eller har en formatversjon som ikke støttes.',
    ],

    'platform-module' => [
        'name' => 'Stamdata (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Slettede entitetstyper: :count.',
        'mass-delete-skipped' => 'Ikke slettet fordi de fortsatt har poster: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Overordnede typer',
            'records-count'        => 'Poster',
            'fields-count'         => 'Felt',
            'updated-at'           => 'Oppdatert',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sorteringsrekkefølge',
        ],

        'edit' => [
            'schema-version' => 'Skjema v:version',
        ],

        'form' => [
            'basics'                    => 'Grunnleggende',
            'identity'                  => 'Identitet',
            'hierarchy'                 => 'Hierarki',
            'governance'                => 'Styring',
            'quality-matching'          => 'Kvalitet og samsvar',
            'matching-info'             => 'Innkommende rader fra kildesystemer matches mot eksisterende poster i matchinnboksen.',
            'auto-match-threshold'      => 'Terskel for automatisk match',
            'auto-match-off'            => 'Av',
            'auto-match-threshold-info' => 'La stå tomt for å gjennomgå hver rad. Fra 0,5 til 1: en rad der det beste forslaget scorer minst dette, uten at andre forslag er like høye, godtas automatisk. Typer som krever godkjenning holder fortsatt koblingen til godkjenning.',
            'match-on-write'            => 'Se etter duplikater ved lagring',
            'match-on-write-info'       => 'Hver lagrede post sammenlignes i bakgrunnen med de andre postene ved hjelp av samsvarsreglene. Slå av for å finne duplikater bare i kvalitetsskanninger.',
            'manage-icons'              => 'Administrer ikoner',
            'sort-order'                => 'Sorteringsrekkefølge',
            'sort-order-info'           => 'Lavere tall vises først, fra 0 til 9999; typer med samme tall sorteres etter kode.',
        ],

        'quick-create' => [
            'code-info'             => 'Små bokstaver, sifre og understreker, som starter med en bokstav. Kan ikke endres etter lagring.',
            'code-pattern-info'     => 'Bruk {seq} eller {seq:6} for løpenummeret og {TYPE} for typekoden, for eksempel {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Poster kommer fra flere kildesystemer og blir matchet og slått sammen til én gyllen post.',
                'centralized'   => 'Poster opprettes og vedlikeholdes her og publiseres deretter til systemene som bruker dem.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Samsvarsregel for :target',
        ],

        'show' => [
            'auto-matched' => 'Automatisk matchet',
            'auto-pending' => 'Matchet automatisk; koblingen venter på godkjenning.',
        ],

        'datagrid' => [
            'decision' => 'Beslutning',
            'auto'     => 'Automatisk matchet',
            'manual'   => 'Manuell',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Brukes av',
            'empty'        => 'Ingen eksportjobb publiserer poster av denne typen og statusen.',
            'job'          => 'Eksportjobb',
            'format'       => 'Format',
            'mode'         => 'Modus',
            'full'         => 'Full',
            'delta'        => 'Kun endringer',
            'last-run-at'  => 'Siste fullførte kjøring',
            'no-run'       => 'Aldri kjørt',
            'record-state' => 'Denne posten',
            'states'       => [
                'never_run'  => 'Ikke eksportert ennå',
                'pending'    => 'Endret siden siste kjøring',
                'included'   => 'Med i siste kjøring',
                'up_to_date' => 'Oppdatert',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikonet ble opprettet.',
        'update-success' => 'Ikonet ble oppdatert.',
        'delete-success' => 'Ikonet ble slettet.',
        'delete-in-use'  => '{1} Dette ikonet brukes av én enhetstype og kan ikke slettes.|[2,*] Dette ikonet brukes av :count enhetstyper og kan ikke slettes.',
        'index'          => [
            'title'      => 'Ikoner',
            'create-btn' => 'Legg til ikon',
        ],

        'create' => [
            'title'    => 'Legg til ikon',
            'save-btn' => 'Lagre ikon',
        ],

        'edit' => [
            'title'    => 'Rediger ikon',
            'save-btn' => 'Lagre ikon',
        ],

        'form' => [
            'code'      => 'Kode',
            'name'      => 'Navn',
            'file'      => 'Ikonfil',
            'file-info' => 'SVG, PNG eller WebP, opptil :size KB. Bruk én form på en gjennomsiktig bakgrunn: den overtar temafargen.',
            'preview'   => 'Forhåndsvisning',
            'usage'     => '{0} Ingen enhetstype bruker dette ikonet ennå.|{1} Brukes av én enhetstype.|[2,*] Brukes av :count enhetstyper.',
        ],

        'library' => [
            'barcode'    => 'Strekkode',
            'boxes'      => 'Materialer',
            'briefcase'  => 'Leverandør',
            'building_2' => 'Selskap',
            'cpu'        => 'Eiendel',
            'factory'    => 'Fabrikk',
            'file_text'  => 'Kontrakt',
            'globe'      => 'Land',
            'handshake'  => 'Partner',
            'landmark'   => 'Juridisk enhet',
            'map_pin'    => 'Lokasjon',
            'network'    => 'Hierarki',
            'package'    => 'Produkt',
            'store'      => 'Butikk',
            'tag'        => 'Kategori',
            'truck'      => 'Underleverandør',
            'user_round' => 'Kontakt',
            'users'      => 'Kunde',
            'warehouse'  => 'Lager',
            'wrench'     => 'Utstyr',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikon',
            'code'    => 'Kode',
            'name'    => 'Navn',
            'usage'   => 'Brukt av',
            'edit'    => 'Rediger',
            'delete'  => 'Slett',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Samsvar',
        'title'          => 'Samsvarsregler',
        'info'           => 'Bestem hvordan poster av denne typen sammenlignes for å finne duplikater og matche innkommende rader. Uten regler sammenlignes koden og etiketten.',
        'create-btn'     => 'Legg til samsvarsregel',
        'create-success' => 'Samsvarsregelen ble opprettet.',
        'update-success' => 'Samsvarsregelen ble oppdatert.',
        'delete-success' => 'Samsvarsregelen ble slettet.',

        'create' => [
            'title' => 'Legg til samsvarsregel',
        ],

        'edit' => [
            'title' => 'Rediger samsvarsregel',
        ],

        'form' => [
            'target'            => 'Sammenlign',
            'select-target'     => 'Velg hva som skal sammenlignes',
            'target-info'       => 'Postens kode, etiketten eller et felt i denne enhetstypen.',
            'comparator'        => 'Sammenligningsmetode',
            'select-comparator' => 'Velg en sammenligningsmetode',
            'comparator-info'   => 'Hvordan de to verdiene sammenlignes, f.eks. Omtrentlig for navn med skrivefeil eller Numerisk for beløp.',
            'weight'            => 'Vekt',
            'weight-info'       => 'Hvor mye denne regelen teller, fra 0,1 til 100. Poengsummen er det vektede gjennomsnittet av reglene som den innkommende raden har en verdi for.',
            'tolerance'         => 'Toleranse',
            'tolerance-info'    => 'Den største forskjellen mellom to tall som fortsatt regnes som lik. La stå tomt for å matche bare like tall.',
            'days'              => 'Datovindu (dager)',
            'days-info'         => 'Hvor mange dager to datoer høyst kan ligge fra hverandre og fortsatt regnes som like. La stå tomt for å matche bare samme dag.',
            'sort-order'        => 'Sorteringsrekkefølge',
            'is-blocking'       => 'Blokkeringsnøkkel',
            'is-blocking-info'  => 'Slå også opp postene som har nøyaktig denne verdien, selv når koden og etiketten ser forskjellige ut. Feltene må være søkbare.',
            'save-btn'          => 'Lagre samsvarsregel',
        ],

        'targets' => [
            'code'  => 'Kode',
            'label' => 'Etikett',
        ],

        'comparators' => [
            'exact'    => 'Nøyaktig',
            'fuzzy'    => 'Omtrentlig',
            'token'    => 'Samme ord',
            'phonetic' => 'Låter likt',
            'numeric'  => 'Numerisk',
            'date'     => 'Dato',
        ],

        'datagrid' => [
            'target'     => 'Sammenlign',
            'comparator' => 'Sammenligningsmetode',
            'weight'     => 'Vekt',
            'blocking'   => 'Blokkeringsnøkkel',
            'sort-order' => 'Sorteringsrekkefølge',
            'yes'        => 'Ja',
            'no'         => 'Nei',
            'edit'       => 'Rediger',
            'delete'     => 'Slett',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Bare koden, etiketten eller et søkbart felt kan være en blokkeringsnøkkel.',
            'too-many'                => 'En enhetstype kan ha maksimalt :count samsvarsregler.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Overlevelse',
        'title'          => 'Overlevelsesregler',
        'info'           => 'Bestem hvilken verdi golden record beholder når to poster av denne typen slås sammen. Uten regler beholder den overlevende posten verdien sin med mindre den er tom.',
        'create-btn'     => 'Legg til overlevelsesregel',
        'create-success' => 'Overlevelsesregel opprettet.',
        'update-success' => 'Overlevelsesregel oppdatert.',
        'delete-success' => 'Overlevelsesregel slettet.',

        'create' => [
            'title' => 'Legg til overlevelsesregel',
        ],

        'edit' => [
            'title' => 'Rediger overlevelsesregel',
        ],

        'form' => [
            'target'            => 'Gjelder for',
            'select-target'     => 'Velg hva regelen gjelder for',
            'target-info'       => 'Et felt, postens etikett eller alle felt uten egen regel.',
            'strategy'          => 'Strategi',
            'select-strategy'   => 'Velg en strategi',
            'strategy-info'     => 'Hvordan den overlevende verdien velges. Dataansvarlige kan fortsatt endre hvert valg på sammenslåingsskjermen.',
            'source-order'      => 'Kilderekkefølge',
            'select-sources'    => 'Velg kildesystemer',
            'source-order-info' => 'Det mest pålitelige kildesystemet først. Utelatte systemer følger prioriteten sin. La stå tomt for å bruke bare prioriteten til kildesystemene.',
            'sort-order'        => 'Sorteringsrekkefølge',
            'save-btn'          => 'Lagre overlevelsesregel',
        ],

        'targets' => [
            'default' => 'Standard for alle felt',
            'label'   => 'Etikett',
        ],

        'strategies' => [
            'source_priority' => 'Mest pålitelige kilde',
            'most_recent'     => 'Sist oppdatert',
            'most_complete'   => 'Mest komplett',
            'keep_target'     => 'Behold den overlevende',
            'manual'          => 'Dataansvarlig bestemmer',
        ],

        'datagrid' => [
            'target'     => 'Gjelder for',
            'strategy'   => 'Strategi',
            'sort-order' => 'Sorteringsrekkefølge',
            'edit'       => 'Rediger',
            'delete'     => 'Slett',
        ],

        'validation' => [
            'target-taken' => 'Denne enhetstypen har allerede en overlevelsesregel for dette målet.',
            'too-many'     => 'En enhetstype kan ha maksimalt :count overlevelsesregler.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Bla gjennom :type under',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Øverste nivå',
        ],

        'datagrid' => [
            'erase'         => 'Slett permanent',
            'parent'        => 'Overordnet',
            'top-level'     => 'Øverste nivå',
            'children'      => 'Underordnede',
            'view-children' => 'Vis de :count postene under denne',
        ],

        'erase' => [
            'btn'             => 'Slett permanent',
            'confirm-title'   => 'Slette denne posten permanent?',
            'confirm-message' => 'Alle verdier, etiketten og koden for denne posten og for duplikatene som er slått sammen med den, fjernes sammen med historikk, endringsforespørsler og kvalitetsproblemer. Posten beholdes som en slettet plassholder slik at kilde-ID-er og lenker fortsatt fungerer. Dette kan ikke angres.',
            'success'         => 'Posten er slettet permanent.',
            'not-retired'     => 'Bare en pensjonert post kan slettes. Pensjoner den først.',
        ],

        'mass-retire' => [
            'success' => ':count post(er) pensjonert.',
            'pending' => ':count pensjonering(er) sendt til godkjenning.',
            'skipped' => ':count post(er) hoppet over fordi de ikke er utkast eller aktive, eller er endret av noen andre.',
            'none'    => 'Ingen av de valgte postene kunne pensjoneres.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Godkjenning kreves: endringsforespørsel #:id',
            'description'           => ':requester sendte inn en ":operation"-endring på :type til din godkjenning.',
            'description-automated' => 'En automatisk ":operation"-endring på :type venter på din godkjenning.',
        ],
    ],

    'access' => [
        'menu'           => 'Datatilgang',
        'acl'            => [
            'index'  => 'Datatilgang',
            'create' => 'Opprett',
            'edit'   => 'Rediger',
            'delete' => 'Slett',
        ],
        'principals' => [
            'role' => 'Rolle',
            'user' => 'Bruker',
        ],
        'actions' => [
            'view'    => 'Vis',
            'edit'    => 'Rediger',
            'approve' => 'Godkjenn',
        ],
        'index' => [
            'title'      => 'Datatilgang',
            'create-btn' => 'Legg til tildeling',
            'info'       => 'Tildelinger avgjør hvilke poster av avgrensede enhetstyper hver rolle eller bruker får tilgang til: den valgte posten og alt under den. Tilgang fra en brukers rolle og fra tildelinger gitt til brukeren legges sammen.',
        ],
        'create' => [
            'title' => 'Legg til datatilgangstildeling',
        ],
        'edit' => [
            'title' => 'Rediger datatilgangstildeling',
        ],
        'form' => [
            'save-btn'         => 'Lagre tildeling',
            'principal'        => 'Tildelt til',
            'principal-info'   => 'En rolletildeling gjelder alle brukere med den rollen. Tildel til én enkelt bruker for et unntak.',
            'principal-type'   => 'Tildel til',
            'role'             => 'Rolle',
            'user'             => 'Bruker',
            'scope'            => 'Omfang',
            'scope-info'       => 'Tildelingen dekker den valgte posten og alle poster under den i hierarkiet.',
            'root'             => 'Rot for undertre',
            'root-info'        => 'For eksempel et sted eller et anlegg. Poster under den arver tildelingen.',
            'entity-type'      => 'Enhetstype',
            'entity-type-info' => 'Begrens tildelingen til én avgrenset enhetstype, eller la den stå tom for alle avgrensede typer.',
            'any-entity-type'  => 'Alle avgrensede enhetstyper',
            'actions'          => 'Handlinger',
            'actions-info'     => 'Vis leser poster, Rediger endrer, avvikler, slår sammen og oppretter poster under roten, Godkjenn avgjør tilbakeholdte endringer. Rediger og Godkjenn omfatter Vis.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Type',
            'principal'         => 'Tildelt til',
            'missing-principal' => 'Slettet',
            'entity-type'       => 'Enhetstype',
            'root'              => 'Rot for undertre',
            'actions'           => 'Handlinger',
            'edit'              => 'Rediger',
            'delete'            => 'Slett',
        ],
        'role-card' => [
            'title'  => 'Tilgang til stamdata',
            'info'   => 'Hvilke poster av avgrensede enhetstyper brukere med denne rollen får tilgang til. Hver tildeling dekker rotposten sin og alt under den.',
            'empty'  => 'Denne rollen har ingen tildelinger for stamdatatilgang ennå.',
            'remove' => 'Fjern',
            'add'    => 'Legg til en tildeling',
        ],
        'entity-type' => [
            'is-scoped'      => 'Begrens tilgang etter undertre',
            'is-scoped-info' => 'Når dette er slått på, får brukere bare tilgang til poster av denne typen innenfor undertrærne som er tildelt rollen deres eller dem selv på skjermbildet Datatilgang. La det stå av for delte referansedata.',
        ],
        'validation' => [
            'record-not-found'    => 'Velg en post du kan se som rot for undertreet.',
            'parent-out-of-scope' => 'Velg en overordnet post i et undertre der du har redigeringstilgang.',
        ],
        'create-success' => 'Datatilgangstildelingen er lagt til.',
        'update-success' => 'Datatilgangstildelingen er oppdatert.',
        'delete-success' => 'Datatilgangstildelingen er slettet.',
    ],

    'install' => [
        'migrating' => 'Kjører migreringene for stamdata.',
        'demo'      => 'Installerer demodata for produksjon.',
        'done'      => 'Stamdatahåndtering er installert. Aktive moduler: :modules.',
    ],
];
