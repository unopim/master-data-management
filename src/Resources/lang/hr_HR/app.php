<?php

return [
    'menu' => [
        'master-data' => 'Matični podaci',
        'icons'       => 'Ikone',
    ],

    'acl' => [
        'master-data' => 'Matični podaci',
        'icons'       => [
            'index'  => 'Ikone',
            'create' => 'Stvori',
            'edit'   => 'Uredi',
            'delete' => 'Izbriši',
        ],

        'records' => [
            'erase' => 'Trajno izbriši',
        ],
    ],

    'dashboard' => [
        'title'      => 'Matični podaci',
        'inside'     => 'Unutar: :types',
        'top-level'  => 'Najviša razina',
        'standalone' => 'Samostalno',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Matični podaci',
            'records'     => 'Zapisi',
            'upsert'      => 'Umetni ili ažuriraj prema ID-u izvornog sustava',
        ],

        'validation-failed'     => 'Provjera valjanosti nije uspjela.',
        'change-held'           => 'Promjena čeka odobrenje.',
        'xref-not-found'        => 'Nijedan zapis ne odgovara ovom vanjskom ID-u.',
        'entity-type-not-found' => 'Vrsta entiteta nije pronađena.',
        'record-not-found'      => 'Zapis nije pronađen.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Vrsta entiteta',
            'source-system'       => 'Izvorni sustav',
            'code-list'           => 'Popis kodova',
            'entity-type-info'    => 'Vrsta entiteta čije zapise datoteka sadrži.',
            'source-system-info'  => 'Sustav iz kojeg datoteka dolazi. Njegovi vanjski ID-ovi čitaju se iz stupca external_id.',
            'code-list-info'      => 'Popis u koji se uvozi. Kodovi se upisuju u njegovu verziju nacrta, koju objavljujete zasebno.',
            'mapping-info'        => 'Jedan redak po preimenovanom stupcu: stupac datoteke=kod polja ili kod polja-jezik za polje po jeziku, npr. Asset Num=external_id i Description=name. Stupci bez retka čitaju se pod vlastitim imenom.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Odaberite aktivnu vrstu entiteta čije zapise smijete stvarati ili uređivati.',
            'source-system-invalid'              => 'Odaberite aktivni izvorni sustav.',
            'code-list-invalid'                  => 'Odaberite postojeći popis kodova koji smijete uređivati.',
            'external-id-without-source-system'  => 'Datoteka ima stupac s vanjskim ID-ovima (:column). Odaberite izvorni sustav iz kojeg dolazi ili uklonite stupac.',
        ],

        'schedule' => [
            'job-not-found'  => 'Nijedan zadatak uvoza nema kod :code.',
            'not-masterdata' => 'Zadatak :code nije uvoz matičnih podataka.',
            'no-user'        => 'Navedite administratora pod kojim se uvoz izvodi pomoću --user ili MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Nijedan aktivni administrator nema e-poštu :email.',
            'file-not-found' => 'Datoteka :path nije pronađena na disku :disk.',
            'nothing'        => 'Nijedna datoteka ne čeka u :path na disku :disk.',
            'queued'         => 'Datoteka :file stavljena je u red za zadatak :code, prati se kao #:id.',
            'imported'       => 'Datoteka :file uvezena je za zadatak :code, prati se kao #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Proizvodna demonstracija je spremna: :types vrsta entiteta, :systems izvornih sustava, :records zapisa u stablu lokacija, :xrefs unakrsnih referenci, :lists popisa šifri, :fields polja, :aliases aliasa.',
        'invalid-template' => 'Predložak :name nije moguće pročitati ili ima nepodržanu verziju formata.',
    ],

    'platform-module' => [
        'name' => 'Matični podaci (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Izbrisane vrste entiteta: :count.',
        'mass-delete-skipped' => 'Nisu izbrisane jer još imaju zapise: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Nadređene vrste',
            'records-count'        => 'Zapisi',
            'fields-count'         => 'Polja',
            'updated-at'           => 'Ažurirano',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Redoslijed',
        ],

        'edit' => [
            'schema-version' => 'Shema v:version',
        ],

        'form' => [
            'basics'                    => 'Osnovno',
            'identity'                  => 'Identitet',
            'hierarchy'                 => 'Hijerarhija',
            'governance'                => 'Upravljanje',
            'quality-matching'          => 'Kvaliteta i uparivanje',
            'matching-info'             => 'Dolazni retci iz izvornih sustava uparuju se s postojećim zapisima u pretincu za uparivanje.',
            'auto-match-threshold'      => 'Prag automatskog uparivanja',
            'auto-match-off'            => 'Isključeno',
            'auto-match-threshold-info' => 'Ostavite prazno za pregled svakog retka. Od 0,5 do 1: redak čiji najbolji prijedlog postigne barem ovu vrijednost, a nijedan drugi prijedlog nije jednako visok, prihvaća se automatski. Kod tipova koji zahtijevaju odobrenje poveznica i dalje čeka odobrenje.',
            'match-on-write'            => 'Provjeri duplikate pri spremanju',
            'match-on-write-info'       => 'Svaki spremljeni zapis u pozadini se uspoređuje s ostalim zapisima prema pravilima uparivanja. Isključite kako biste duplikate tražili samo u provjerama kvalitete.',
            'manage-icons'              => 'Upravljanje ikonama',
            'sort-order'                => 'Redoslijed',
            'sort-order-info'           => 'Manji brojevi prikazuju se prvi, od 0 do 9999; vrste s istim brojem poredane su po kodu.',
        ],

        'quick-create' => [
            'code-info'             => 'Mala slova, znamenke i podvlake, počevši slovom. Nakon spremanja ne može se mijenjati.',
            'code-pattern-info'     => 'Koristite {seq} ili {seq:6} za redni broj i {TYPE} za kod tipa, na primjer {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Zapisi stižu iz više izvornih sustava te se uparuju i spajaju u jedan zlatni zapis.',
                'centralized'   => 'Zapisi se ovdje stvaraju i održavaju, a zatim objavljuju sustavima koji ih koriste.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Pravilo uparivanja za :target',
        ],

        'show' => [
            'auto-matched' => 'Automatski upareno',
            'auto-pending' => 'Automatski upareno; poveznica čeka odobrenje.',
        ],

        'datagrid' => [
            'decision' => 'Odluka',
            'auto'     => 'Automatski upareno',
            'manual'   => 'Ručno',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Koristi',
            'empty'        => 'Nijedan izvozni posao ne objavljuje zapise ove vrste i statusa.',
            'job'          => 'Izvozni posao',
            'format'       => 'Format',
            'mode'         => 'Način',
            'full'         => 'Potpuno',
            'delta'        => 'Samo promjene',
            'last-run-at'  => 'Posljednje dovršeno pokretanje',
            'no-run'       => 'Nikad pokrenuto',
            'record-state' => 'Ovaj zapis',
            'states'       => [
                'never_run'  => 'Još nije izvezeno',
                'pending'    => 'Promijenjeno od posljednjeg pokretanja',
                'included'   => 'Uključeno u posljednje pokretanje',
                'up_to_date' => 'Ažurno',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikona je uspješno stvorena.',
        'update-success' => 'Ikona je uspješno ažurirana.',
        'delete-success' => 'Ikona je uspješno izbrisana.',
        'delete-in-use'  => '{1} Ovu ikonu prikazuje jedna vrsta entiteta i ne može se izbrisati.|[2,*] Ovu ikonu prikazuje :count vrsta entiteta i ne može se izbrisati.',
        'index'          => [
            'title'      => 'Ikone',
            'create-btn' => 'Dodaj ikonu',
        ],

        'create' => [
            'title'    => 'Dodaj ikonu',
            'save-btn' => 'Spremi ikonu',
        ],

        'edit' => [
            'title'    => 'Uredi ikonu',
            'save-btn' => 'Spremi ikonu',
        ],

        'form' => [
            'code'      => 'Šifra',
            'name'      => 'Naziv',
            'file'      => 'Datoteka ikone',
            'file-info' => 'SVG, PNG ili WebP, do :size KB. Upotrijebite jedan oblik na prozirnoj pozadini: preuzima boju teme.',
            'preview'   => 'Pregled',
            'usage'     => '{0} Nijedna vrsta entiteta još ne prikazuje ovu ikonu.|{1} Prikazuje je jedna vrsta entiteta.|[2,*] Prikazuje je :count vrsta entiteta.',
        ],

        'library' => [
            'barcode'    => 'Crtični kod',
            'boxes'      => 'Materijali',
            'briefcase'  => 'Dobavljač',
            'building_2' => 'Tvrtka',
            'cpu'        => 'Imovina',
            'factory'    => 'Pogon',
            'file_text'  => 'Ugovor',
            'globe'      => 'Država',
            'handshake'  => 'Partner',
            'landmark'   => 'Pravni subjekt',
            'map_pin'    => 'Lokacija',
            'network'    => 'Hijerarhija',
            'package'    => 'Proizvod',
            'store'      => 'Trgovina',
            'tag'        => 'Kategorija',
            'truck'      => 'Isporučitelj',
            'user_round' => 'Kontakt',
            'users'      => 'Kupac',
            'warehouse'  => 'Skladište',
            'wrench'     => 'Oprema',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikona',
            'code'    => 'Šifra',
            'name'    => 'Naziv',
            'usage'   => 'Koristi',
            'edit'    => 'Uredi',
            'delete'  => 'Izbriši',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Uparivanje',
        'title'          => 'Pravila uparivanja',
        'info'           => 'Odredite kako se zapisi ove vrste uspoređuju radi pronalaska duplikata i uparivanja dolaznih redaka. Bez pravila uspoređuju se kod i oznaka.',
        'create-btn'     => 'Dodaj pravilo uparivanja',
        'create-success' => 'Pravilo uparivanja je uspješno stvoreno.',
        'update-success' => 'Pravilo uparivanja je uspješno ažurirano.',
        'delete-success' => 'Pravilo uparivanja je uspješno izbrisano.',

        'create' => [
            'title' => 'Dodaj pravilo uparivanja',
        ],

        'edit' => [
            'title' => 'Uredi pravilo uparivanja',
        ],

        'form' => [
            'target'            => 'Usporedi',
            'select-target'     => 'Odaberite što se uspoređuje',
            'target-info'       => 'Kod zapisa, njegova oznaka ili polje ove vrste entiteta.',
            'comparator'        => 'Način usporedbe',
            'select-comparator' => 'Odaberite način usporedbe',
            'comparator-info'   => 'Kako se dvije vrijednosti uspoređuju, npr. Približno za imena s tipfelerima ili Brojčano za iznose.',
            'weight'            => 'Težina',
            'weight-info'       => 'Koliko ovo pravilo vrijedi, od 0,1 do 100. Rezultat je ponderirani prosjek pravila za koja dolazni redak ima vrijednost.',
            'tolerance'         => 'Tolerancija',
            'tolerance-info'    => 'Najveća razlika između dvaju brojeva koja se još smatra sličnom. Ostavite prazno za uparivanje samo jednakih brojeva.',
            'days'              => 'Raspon datuma (dani)',
            'days-info'         => 'Najveći broj dana između dvaju datuma koji se još smatraju sličnima. Ostavite prazno za uparivanje samo istog dana.',
            'sort-order'        => 'Redoslijed',
            'is-blocking'       => 'Ključ blokiranja',
            'is-blocking-info'  => 'Pronalazi i zapise s točno istom vrijednošću, čak i kad se njihov kod i oznaka razlikuju. Polja moraju biti pretraživa.',
            'save-btn'          => 'Spremi pravilo uparivanja',
        ],

        'targets' => [
            'code'  => 'Kod',
            'label' => 'Oznaka',
        ],

        'comparators' => [
            'exact'    => 'Točno',
            'fuzzy'    => 'Približno',
            'token'    => 'Iste riječi',
            'phonetic' => 'Zvuči slično',
            'numeric'  => 'Brojčano',
            'date'     => 'Datum',
        ],

        'datagrid' => [
            'target'     => 'Usporedi',
            'comparator' => 'Način usporedbe',
            'weight'     => 'Težina',
            'blocking'   => 'Ključ blokiranja',
            'sort-order' => 'Redoslijed',
            'yes'        => 'Da',
            'no'         => 'Ne',
            'edit'       => 'Uredi',
            'delete'     => 'Izbriši',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Ključ blokiranja može biti samo kod, oznaka ili pretraživo polje.',
            'too-many'                => 'Vrsta entiteta može imati najviše :count pravila uparivanja.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevlast vrijednosti',
        'title'          => 'Pravila prevlasti vrijednosti',
        'info'           => 'Odredite koju vrijednost zadržava glavni zapis kada se dva zapisa ove vrste spoje. Bez pravila preživjeli zapis zadržava svoju vrijednost osim ako je prazna.',
        'create-btn'     => 'Dodaj pravilo prevlasti vrijednosti',
        'create-success' => 'Pravilo prevlasti vrijednosti uspješno je stvoreno.',
        'update-success' => 'Pravilo prevlasti vrijednosti uspješno je ažurirano.',
        'delete-success' => 'Pravilo prevlasti vrijednosti uspješno je izbrisano.',

        'create' => [
            'title' => 'Dodaj pravilo prevlasti vrijednosti',
        ],

        'edit' => [
            'title' => 'Uredi pravilo prevlasti vrijednosti',
        ],

        'form' => [
            'target'            => 'Primjenjuje se na',
            'select-target'     => 'Odaberite na što se pravilo primjenjuje',
            'target-info'       => 'Polje, oznaka zapisa ili sva polja bez vlastitog pravila.',
            'strategy'          => 'Strategija',
            'select-strategy'   => 'Odaberite strategiju',
            'strategy-info'     => 'Kako se bira preživjela vrijednost. Upravitelji podataka i dalje mogu promijeniti svaki odabir na zaslonu za spajanje.',
            'source-order'      => 'Redoslijed izvora',
            'select-sources'    => 'Odaberite izvorne sustave',
            'source-order-info' => 'Najpouzdaniji izvorni sustav prvi. Izostavljeni sustavi slijede svoj prioritet. Ostavite prazno za korištenje samo prioriteta izvornih sustava.',
            'sort-order'        => 'Redoslijed',
            'save-btn'          => 'Spremi pravilo prevlasti vrijednosti',
        ],

        'targets' => [
            'default' => 'Zadano za sva polja',
            'label'   => 'Oznaka',
        ],

        'strategies' => [
            'source_priority' => 'Najpouzdaniji izvor',
            'most_recent'     => 'Posljednje ažurirano',
            'most_complete'   => 'Najpotpunije',
            'keep_target'     => 'Zadrži preživjeli zapis',
            'manual'          => 'Odlučuje upravitelj podataka',
        ],

        'datagrid' => [
            'target'     => 'Primjenjuje se na',
            'strategy'   => 'Strategija',
            'sort-order' => 'Redoslijed',
            'edit'       => 'Uredi',
            'delete'     => 'Izbriši',
        ],

        'validation' => [
            'target-taken' => 'Ova vrsta entiteta već ima pravilo prevlasti vrijednosti za ovaj cilj.',
            'too-many'     => 'Vrsta entiteta može imati najviše :count pravila prevlasti vrijednosti.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validacija',
        'title'          => 'Pravila validacije',
        'info'           => 'Provjeravajte vrijednosti polja izvan njihova tipa. Blokirajuća pravila odbijaju spremanje, uvoz ili API zapis; pravila upozorenja ih propuštaju i prijavljuju upozorenje o kvaliteti podataka.',
        'create-btn'     => 'Dodaj pravilo validacije',
        'create-success' => 'Pravilo validacije uspješno je stvoreno.',
        'update-success' => 'Pravilo validacije uspješno je ažurirano.',
        'delete-success' => 'Pravilo validacije uspješno je izbrisano.',

        'create' => [
            'title' => 'Dodaj pravilo validacije',
        ],

        'edit' => [
            'title' => 'Uredi pravilo validacije',
        ],

        'form' => [
            'field'            => 'Polje',
            'select-field'     => 'Odaberite polje',
            'type'             => 'Pravilo',
            'select-type'      => 'Odaberite pravilo',
            'severity'         => 'Ozbiljnost',
            'severity-info'    => '„Blokiraj” odbija vrijednosti koje krše pravilo. „Upozori” ih sprema i prijavljuje upozorenje o kvaliteti.',
            'pattern'          => 'Uzorak',
            'pattern-info'     => 'Regularni izraz s razdjelnicima, na primjer /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Vrijednost',
            'min-length'       => 'Minimalna duljina',
            'max-length'       => 'Maksimalna duljina',
            'operator'         => 'Usporedba',
            'other-field'      => 'Uspoređuje se s',
            'other-field-info' => 'Pravilo je zadovoljeno dok je drugo polje prazno.',
            'codes'            => 'Dopušteni kodovi',
            'codes-info'       => 'Jedan kod po retku ili odvojeni zarezima. Vrijednosti se uspoređuju točno.',
            'after'            => 'Ne prije',
            'before'           => 'Ne poslije',
            'date-info'        => 'Datum poput 2020-01-01 ili relativni datum poput today ili -18 years. Ostavite jedno prazno za otvoreni raspon.',
            'messages'         => 'Poruka o pogrešci',
            'messages-info'    => 'Prikazuje se kad vrijednost krši pravilo, na jeziku osobe ili API klijenta. Ostavite prazno za zadanu poruku. :attribute označava naziv polja.',
            'sort-order'       => 'Redoslijed sortiranja',
            'save-btn'         => 'Spremi pravilo validacije',
        ],

        'types' => [
            'regex'        => 'Odgovara uzorku',
            'min'          => 'Minimalna vrijednost',
            'max'          => 'Maksimalna vrijednost',
            'length'       => 'Duljina',
            'cross_field'  => 'Uspoređeno s drugim poljem',
            'one_of_codes' => 'Jedan od navedenih kodova',
            'date_range'   => 'Raspon datuma',
        ],

        'severities' => [
            'block' => 'Blokiraj',
            'warn'  => 'Upozori',
        ],

        'operators' => [
            'eq'  => 'jednako',
            'neq' => 'različito od',
            'gt'  => 'veće od',
            'gte' => 'najmanje',
            'lt'  => 'manje od',
            'lte' => 'najviše',
        ],

        'messages' => [
            'default'      => ':attribute nije valjan.',
            'regex'        => ':attribute nema očekivani format.',
            'min'          => ':attribute mora biti najmanje :value.',
            'max'          => ':attribute mora biti najviše :value.',
            'length'       => ':attribute mora imati između :min i :max znakova.',
            'cross_field'  => ':attribute mora biti :operator :field.',
            'one_of_codes' => ':attribute mora biti jedan od: :codes.',
            'date_range'   => ':attribute mora biti datum unutar dopuštenog raspona.',
        ],

        'datagrid' => [
            'field'      => 'Polje',
            'type'       => 'Pravilo',
            'severity'   => 'Ozbiljnost',
            'sort-order' => 'Redoslijed sortiranja',
            'edit'       => 'Uredi',
            'delete'     => 'Izbriši',
        ],

        'validation' => [
            'too-many'     => 'Tip entiteta može imati najviše :count pravila validacije.',
            'invalid-date' => 'Unesite datum ili relativni datum poput today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Standardizacija',
        'info'           => 'Očistite vrijednosti prije nego što se validiraju i spreme, iz administracije, uvoza i API-ja. Transformacije polja izvršavaju se prema redoslijedu sortiranja.',
        'create-btn'     => 'Dodaj transformaciju',
        'create-success' => 'Transformacija je uspješno stvorena.',
        'update-success' => 'Transformacija je uspješno ažurirana.',
        'delete-success' => 'Transformacija je uspješno izbrisana.',

        'create' => [
            'title' => 'Dodaj transformaciju',
        ],

        'edit' => [
            'title' => 'Uredi transformaciju',
        ],

        'form' => [
            'field'            => 'Polje',
            'select-field'     => 'Odaberite polje',
            'transform'        => 'Transformacija',
            'select-transform' => 'Odaberite transformaciju',
            'transform-info'   => 'Primjenjuje se samo na tekstualne vrijednosti; ostale vrijednosti ostaju nepromijenjene.',
            'sort-order'       => 'Redoslijed sortiranja',
            'sort-order-info'  => 'Transformacije istog polja izvršavaju se od najnižeg redoslijeda sortiranja prema višem.',
            'save-btn'         => 'Spremi transformaciju',
        ],

        'types' => [
            'trim'            => 'Ukloni okolne razmake',
            'lower'           => 'Mala slova',
            'upper'           => 'Velika slova',
            'title'           => 'Velika početna slova',
            'collapse_spaces' => 'Sažmi ponovljene razmake',
            'email_normalize' => 'Normaliziraj adresu e-pošte',
            'phone_e164'      => 'Normaliziraj telefonski broj (E.164)',
            'strip_non_alnum' => 'Zadrži samo slova i znamenke',
        ],

        'datagrid' => [
            'field'      => 'Polje',
            'transform'  => 'Transformacija',
            'sort-order' => 'Redoslijed sortiranja',
            'edit'       => 'Uredi',
            'delete'     => 'Izbriši',
        ],

        'validation' => [
            'taken'    => 'Ovo polje već izvršava ovu transformaciju.',
            'too-many' => 'Tip entiteta može imati najviše :count transformacija.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Upozorenje validacije',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Pregledaj :type ispod',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Najviša razina',
        ],

        'datagrid' => [
            'erase'         => 'Trajno izbriši',
            'parent'        => 'Nadređeni',
            'top-level'     => 'Najviša razina',
            'children'      => 'Podređeni',
            'view-children' => 'Prikaži :count zapisa ispod ovoga',
        ],

        'erase' => [
            'btn'             => 'Trajno izbriši',
            'confirm-title'   => 'Trajno izbrisati ovaj zapis?',
            'confirm-message' => 'Uklanjaju se sve vrijednosti, oznaka i kôd ovog zapisa i duplikata spojenih u njega, zajedno s njihovom poviješću, zahtjevima za promjenu i problemima kvalitete. Zapis ostaje kao izbrisani rezervirani zapis kako bi njegovi izvorni ID-ovi i poveznice i dalje radili. Ovo se ne može poništiti.',
            'success'         => 'Zapis je trajno izbrisan.',
            'not-retired'     => 'Moguće je izbrisati samo umirovljeni zapis. Najprije ga umirovite.',
        ],

        'mass-retire' => [
            'success' => ':count zapis(a) umirovljeno.',
            'pending' => ':count umirovljenje(a) poslano na odobrenje.',
            'skipped' => ':count zapis(a) preskočeno jer nisu nacrti ni aktivni ili ih je netko drugi promijenio.',
            'none'    => 'Nijedan od odabranih zapisa nije bilo moguće umiroviti.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Potrebno odobrenje: zahtjev za promjenu #:id',
            'description'           => ':requester je poslao promjenu ":operation" na :type na vaše odobrenje.',
            'description-automated' => 'Automatska promjena ":operation" na :type čeka vaše odobrenje.',
        ],
    ],

    'access' => [
        'menu'           => 'Pristup podacima',
        'acl'            => [
            'index'  => 'Pristup podacima',
            'create' => 'Stvori',
            'edit'   => 'Uredi',
            'delete' => 'Izbriši',
        ],
        'principals' => [
            'role' => 'Uloga',
            'user' => 'Korisnik',
        ],
        'actions' => [
            'view'    => 'Pregled',
            'edit'    => 'Uređivanje',
            'approve' => 'Odobravanje',
        ],
        'index' => [
            'title'      => 'Pristup podacima',
            'create-btn' => 'Dodaj dozvolu',
            'info'       => 'Dozvole određuju kojim zapisima ograničenih vrsta entiteta svaka uloga ili korisnik ima pristup: odabranom zapisu i svemu ispod njega. Pristup iz korisnikove uloge i dozvole dodijeljene korisniku zbrajaju se.',
        ],
        'create' => [
            'title' => 'Dodaj dozvolu za pristup podacima',
        ],
        'edit' => [
            'title' => 'Uredi dozvolu za pristup podacima',
        ],
        'form' => [
            'save-btn'         => 'Spremi dozvolu',
            'principal'        => 'Dodijeljeno',
            'principal-info'   => 'Dozvola uloge odnosi se na svakog korisnika s tom ulogom. Dodijelite je pojedinom korisniku za iznimku.',
            'principal-type'   => 'Dodijeli',
            'role'             => 'Uloga',
            'user'             => 'Korisnik',
            'scope'            => 'Opseg',
            'scope-info'       => 'Dozvola obuhvaća odabrani zapis i svaki zapis ispod njega u hijerarhiji.',
            'root'             => 'Korijen podstabla',
            'root-info'        => 'Na primjer lokacija ili pogon. Zapisi ispod njega nasljeđuju dozvolu.',
            'entity-type'      => 'Vrsta entiteta',
            'entity-type-info' => 'Ograničite dozvolu na jednu ograničenu vrstu entiteta ili ostavite prazno za sve ograničene vrste.',
            'any-entity-type'  => 'Sve ograničene vrste entiteta',
            'actions'          => 'Radnje',
            'actions-info'     => 'Pregled čita zapise, Uređivanje mijenja, povlači, spaja i stvara zapise ispod korijena, Odobravanje odlučuje o zadržanim promjenama. Uređivanje i Odobravanje uključuju Pregled.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Vrsta',
            'principal'         => 'Dodijeljeno',
            'missing-principal' => 'Izbrisano',
            'entity-type'       => 'Vrsta entiteta',
            'root'              => 'Korijen podstabla',
            'actions'           => 'Radnje',
            'edit'              => 'Uredi',
            'delete'            => 'Izbriši',
        ],
        'role-card' => [
            'title'  => 'Pristup glavnim podacima',
            'info'   => 'Kojim zapisima ograničenih vrsta entiteta korisnici s ovom ulogom imaju pristup. Svaka dozvola obuhvaća svoj korijenski zapis i sve ispod njega.',
            'empty'  => 'Ova uloga još nema dozvola za pristup glavnim podacima.',
            'remove' => 'Ukloni',
            'add'    => 'Dodaj dozvolu',
        ],
        'entity-type' => [
            'is-scoped'      => 'Ograniči pristup po podstablu',
            'is-scoped-info' => 'Kada je uključeno, korisnici pristupaju samo zapisima ove vrste unutar podstabala dodijeljenih njihovoj ulozi ili njima na zaslonu Pristup podacima. Ostavite isključeno za zajedničke referentne podatke.',
        ],
        'validation' => [
            'record-not-found'    => 'Odaberite zapis koji možete vidjeti kao korijen podstabla.',
            'parent-out-of-scope' => 'Odaberite nadređeni zapis unutar podstabla u kojem imate pristup uređivanju.',
        ],
        'create-success' => 'Dozvola za pristup podacima dodana.',
        'update-success' => 'Dozvola za pristup podacima ažurirana.',
        'delete-success' => 'Dozvola za pristup podacima izbrisana.',
    ],

    'install' => [
        'migrating' => 'Pokretanje migracija glavnih podataka.',
        'demo'      => 'Instalacija demo podataka za proizvodnju.',
        'done'      => 'Upravljanje glavnim podacima je instalirano. Aktivni moduli: :modules.',
    ],
];
