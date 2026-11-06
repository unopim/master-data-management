<?php

return [
    'menu' => [
        'master-data' => 'Perustiedot',
        'icons'       => 'Kuvakkeet',
    ],

    'acl' => [
        'master-data' => 'Perustiedot',
        'icons'       => [
            'index'  => 'Kuvakkeet',
            'create' => 'Luo',
            'edit'   => 'Muokkaa',
            'delete' => 'Poista',
        ],

        'records' => [
            'erase' => 'Poista pysyvästi',
        ],
    ],

    'dashboard' => [
        'title'      => 'Perustiedot',
        'inside'     => 'Sisältyy: :types',
        'top-level'  => 'Ylin taso',
        'standalone' => 'Itsenäinen',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Perustiedot',
            'records'     => 'Tietueet',
            'upsert'      => 'Lisää tai päivitä lähdejärjestelmän tunnuksen mukaan',
        ],

        'validation-failed'     => 'Vahvistus epäonnistui.',
        'change-held'           => 'Muutos odottaa hyväksyntää.',
        'xref-not-found'        => 'Mikään tietue ei vastaa tätä ulkoista tunnusta.',
        'entity-type-not-found' => 'Entiteettityyppiä ei löytynyt.',
        'record-not-found'      => 'Tietuetta ei löytynyt.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Entiteettityyppi',
            'source-system'       => 'Lähdejärjestelmä',
            'code-list'           => 'Koodiluettelo',
            'entity-type-info'    => 'Entiteettityyppi, jonka tietueita tiedosto sisältää.',
            'source-system-info'  => 'Järjestelmä, josta tiedosto tulee. Sen ulkoiset tunnukset luetaan sarakkeesta external_id.',
            'code-list-info'      => 'Luettelo, johon tuodaan. Koodit kirjoitetaan sen luonnosversioon, jonka julkaiset erikseen.',
            'mapping-info'        => 'Yksi rivi kutakin uudelleennimettyä saraketta kohden: tiedoston sarake=kentän koodi tai kentän koodi-kieli kielikohtaiselle kentälle, esim. Asset Num=external_id ja Description=name. Sarakkeet, joilla ei ole riviä, luetaan omalla nimellään.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Valitse aktiivinen entiteettityyppi, jonka tietueita saat luoda tai muokata.',
            'source-system-invalid'              => 'Valitse aktiivinen lähdejärjestelmä.',
            'code-list-invalid'                  => 'Valitse olemassa oleva koodiluettelo, jota saat muokata.',
            'external-id-without-source-system'  => 'Tiedostossa on ulkoisten tunnusten sarake (:column). Valitse lähdejärjestelmä, josta se tulee, tai poista sarake.',
        ],

        'schedule' => [
            'job-not-found'  => 'Millään tuontityöllä ei ole koodia :code.',
            'not-masterdata' => 'Työ :code ei ole perustietojen tuonti.',
            'no-user'        => 'Anna järjestelmänvalvoja, jona tuonti suoritetaan, valinnalla --user tai MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Millään aktiivisella järjestelmänvalvojalla ei ole sähköpostiosoitetta :email.',
            'file-not-found' => 'Tiedostoa :path ei löytynyt levyltä :disk.',
            'nothing'        => 'Levyn :disk kansiossa :path ei odota tiedostoa.',
            'queued'         => 'Tiedosto :file lisättiin jonoon työlle :code, seurantatunnus #:id.',
            'imported'       => 'Tiedosto :file tuotiin työlle :code, seurantatunnus #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Valmistuksen demo on valmis: :types entiteettityyppiä, :systems lähdejärjestelmää, :records tietuetta toimipaikkapuussa, :xrefs ristiviittausta, :lists koodilistaa, :fields kenttää, :aliases aliasta.',
        'invalid-template' => 'Mallipohjaa :name ei voitu lukea tai sen muotoversiota ei tueta.',
    ],

    'platform-module' => [
        'name' => 'Perustiedot (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Poistetut entiteettityypit: :count.',
        'mass-delete-skipped' => 'Ei poistettu, koska niillä on yhä tietueita: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Ylätason tyypit',
            'records-count'        => 'Tietueet',
            'fields-count'         => 'Kentät',
            'updated-at'           => 'Päivitetty',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Järjestys',
        ],

        'edit' => [
            'schema-version' => 'Skeema v:version',
        ],

        'form' => [
            'basics'                    => 'Perustiedot',
            'identity'                  => 'Tunnistus',
            'hierarchy'                 => 'Hierarkia',
            'governance'                => 'Hallinta',
            'quality-matching'          => 'Laatu ja täsmäytys',
            'matching-info'             => 'Lähdejärjestelmistä saapuvat rivit täsmäytetään olemassa oleviin tietueisiin täsmäytyslaatikossa.',
            'auto-match-threshold'      => 'Automaattisen täsmäytyksen kynnys',
            'auto-match-off'            => 'Pois',
            'auto-match-threshold-info' => 'Jätä tyhjäksi, jos haluat tarkistaa jokaisen rivin. 0,5–1: rivi hyväksytään automaattisesti, jos sen paras ehdotus saavuttaa vähintään tämän arvon eikä mikään muu ehdotus ole yhtä korkea. Hyväksyntää vaativissa tyypeissä linkitys odottaa edelleen hyväksyntää.',
            'match-on-write'            => 'Tarkista kaksoiskappaleet tallennettaessa',
            'match-on-write-info'       => 'Jokaista tallennettua tietuetta verrataan taustalla muihin tietueisiin täsmäytyssääntöjen avulla. Poista käytöstä, jos haluat etsiä kaksoiskappaleita vain laatutarkistuksissa.',
            'manage-icons'              => 'Hallinnoi kuvakkeita',
            'sort-order'                => 'Järjestys',
            'sort-order-info'           => 'Pienemmät numerot näytetään ensin, 0–9999; saman numeron tyypit järjestetään koodin mukaan.',
        ],

        'quick-create' => [
            'code-info'             => 'Pieniä kirjaimia, numeroita ja alaviivoja, alkaen kirjaimella. Ei voi muuttaa tallennuksen jälkeen.',
            'code-pattern-info'     => 'Käytä juoksevalle numerolle {seq} tai {seq:6} ja tyyppikoodille {TYPE}, esimerkiksi {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Tietueet tulevat useista lähdejärjestelmistä, ja ne täsmäytetään ja yhdistetään yhdeksi kultaiseksi tietueeksi.',
                'centralized'   => 'Tietueet luodaan ja ylläpidetään täällä ja julkaistaan sitten niitä käyttäviin järjestelmiin.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Täsmäytyssääntö kohteelle :target',
        ],

        'show' => [
            'auto-matched' => 'Täsmäytetty automaattisesti',
            'auto-pending' => 'Täsmäytetty automaattisesti; linkitys odottaa hyväksyntää.',
        ],

        'datagrid' => [
            'decision' => 'Päätös',
            'auto'     => 'Täsmäytetty automaattisesti',
            'manual'   => 'Manuaalinen',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Käyttäjät',
            'empty'        => 'Mikään vientityö ei julkaise tämän tyypin ja tilan tietueita.',
            'job'          => 'Vientityö',
            'format'       => 'Muoto',
            'mode'         => 'Tila',
            'full'         => 'Täysi',
            'delta'        => 'Vain muutokset',
            'last-run-at'  => 'Viimeisin valmis ajo',
            'no-run'       => 'Ei koskaan ajettu',
            'record-state' => 'Tämä tietue',
            'states'       => [
                'never_run'  => 'Ei vielä viety',
                'pending'    => 'Muuttunut viimeisimmän ajon jälkeen',
                'included'   => 'Mukana viimeisimmässä ajossa',
                'up_to_date' => 'Ajan tasalla',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Kuvake luotiin onnistuneesti.',
        'update-success' => 'Kuvake päivitettiin onnistuneesti.',
        'delete-success' => 'Kuvake poistettiin onnistuneesti.',
        'delete-in-use'  => '{1} Tämä kuvake on yhden entiteettityypin käytössä, eikä sitä voi poistaa.|[2,*] Tämä kuvake on :count entiteettityypin käytössä, eikä sitä voi poistaa.',
        'index'          => [
            'title'      => 'Kuvakkeet',
            'create-btn' => 'Lisää kuvake',
        ],

        'create' => [
            'title'    => 'Lisää kuvake',
            'save-btn' => 'Tallenna kuvake',
        ],

        'edit' => [
            'title'    => 'Muokkaa kuvaketta',
            'save-btn' => 'Tallenna kuvake',
        ],

        'form' => [
            'code'      => 'Koodi',
            'name'      => 'Nimi',
            'file'      => 'Kuvaketiedosto',
            'file-info' => 'SVG, PNG tai WebP, enintään :size kt. Käytä yhtä muotoa läpinäkyvällä taustalla: se saa teeman värin.',
            'preview'   => 'Esikatselu',
            'usage'     => '{0} Mikään entiteettityyppi ei vielä käytä tätä kuvaketta.|{1} Yhden entiteettityypin käytössä.|[2,*] :count entiteettityypin käytössä.',
        ],

        'library' => [
            'barcode'    => 'Viivakoodi',
            'boxes'      => 'Materiaalit',
            'briefcase'  => 'Toimittaja',
            'building_2' => 'Yritys',
            'cpu'        => 'Omaisuuserä',
            'factory'    => 'Tehdas',
            'file_text'  => 'Sopimus',
            'globe'      => 'Maa',
            'handshake'  => 'Kumppani',
            'landmark'   => 'Oikeushenkilö',
            'map_pin'    => 'Sijainti',
            'network'    => 'Hierarkia',
            'package'    => 'Tuote',
            'store'      => 'Myymälä',
            'tag'        => 'Luokka',
            'truck'      => 'Tavarantoimittaja',
            'user_round' => 'Yhteyshenkilö',
            'users'      => 'Asiakas',
            'warehouse'  => 'Varasto',
            'wrench'     => 'Laitteisto',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Kuvake',
            'code'    => 'Koodi',
            'name'    => 'Nimi',
            'usage'   => 'Käyttäjä',
            'edit'    => 'Muokkaa',
            'delete'  => 'Poista',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Täsmäytys',
        'title'          => 'Täsmäytyssäännöt',
        'info'           => 'Määritä, miten tämän tyypin tietueita verrataan kaksoiskappaleiden löytämiseksi ja saapuvien rivien täsmäyttämiseksi. Ilman sääntöjä verrataan koodia ja nimiötä.',
        'create-btn'     => 'Lisää täsmäytyssääntö',
        'create-success' => 'Täsmäytyssääntö luotiin onnistuneesti.',
        'update-success' => 'Täsmäytyssääntö päivitettiin onnistuneesti.',
        'delete-success' => 'Täsmäytyssääntö poistettiin onnistuneesti.',

        'create' => [
            'title' => 'Lisää täsmäytyssääntö',
        ],

        'edit' => [
            'title' => 'Muokkaa täsmäytyssääntöä',
        ],

        'form' => [
            'target'            => 'Vertaa',
            'select-target'     => 'Valitse, mitä verrataan',
            'target-info'       => 'Tietueen koodi, sen nimiö tai tämän entiteettityypin kenttä.',
            'comparator'        => 'Vertailutapa',
            'select-comparator' => 'Valitse vertailutapa',
            'comparator-info'   => 'Miten kahta arvoa verrataan, esim. Sumea kirjoitusvirheitä sisältäville nimille tai Numeerinen summille.',
            'weight'            => 'Paino',
            'weight-info'       => 'Kuinka paljon tämä sääntö painaa, 0,1–100. Pistemäärä on niiden sääntöjen painotettu keskiarvo, joille saapuvalla rivillä on arvo.',
            'tolerance'         => 'Toleranssi',
            'tolerance-info'    => 'Suurin kahden luvun välinen ero, joka vielä katsotaan samankaltaiseksi. Jätä tyhjäksi, jos vain yhtä suuret luvut täsmäävät.',
            'days'              => 'Päivämääräikkuna (päivää)',
            'days-info'         => 'Enimmäismäärä päiviä, jonka kaksi päivämäärää voivat olla toisistaan ja silti olla samankaltaisia. Jätä tyhjäksi, jos vain sama päivä täsmää.',
            'sort-order'        => 'Järjestys',
            'is-blocking'       => 'Lohkoavain',
            'is-blocking-info'  => 'Hakee myös tietueet, joilla on täsmälleen sama arvo, vaikka niiden koodi ja nimiö näyttäisivät erilaisilta. Kenttien on oltava haettavissa.',
            'save-btn'          => 'Tallenna täsmäytyssääntö',
        ],

        'targets' => [
            'code'  => 'Koodi',
            'label' => 'Nimiö',
        ],

        'comparators' => [
            'exact'    => 'Tarkka',
            'fuzzy'    => 'Sumea',
            'token'    => 'Samat sanat',
            'phonetic' => 'Kuulostaa samalta',
            'numeric'  => 'Numeerinen',
            'date'     => 'Päivämäärä',
        ],

        'datagrid' => [
            'target'     => 'Vertaa',
            'comparator' => 'Vertailutapa',
            'weight'     => 'Paino',
            'blocking'   => 'Lohkoavain',
            'sort-order' => 'Järjestys',
            'yes'        => 'Kyllä',
            'no'         => 'Ei',
            'edit'       => 'Muokkaa',
            'delete'     => 'Poista',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Vain koodi, nimiö tai haettavissa oleva kenttä voi olla lohkoavain.',
            'too-many'                => 'Entiteettityypillä voi olla enintään :count täsmäytyssääntöä.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Säilytys',
        'title'          => 'Säilytyssäännöt',
        'info'           => 'Päätä, minkä arvon kultainen tietue säilyttää, kun kaksi tämän tyypin tietuetta yhdistetään. Ilman sääntöjä säilytettävä tietue pitää arvonsa, ellei se ole tyhjä.',
        'create-btn'     => 'Lisää säilytyssääntö',
        'create-success' => 'Säilytyssääntö luotu.',
        'update-success' => 'Säilytyssääntö päivitetty.',
        'delete-success' => 'Säilytyssääntö poistettu.',

        'create' => [
            'title' => 'Lisää säilytyssääntö',
        ],

        'edit' => [
            'title' => 'Muokkaa säilytyssääntöä',
        ],

        'form' => [
            'target'            => 'Koskee',
            'select-target'     => 'Valitse, mitä sääntö koskee',
            'target-info'       => 'Kenttä, tietueen nimiö tai kaikki kentät, joilla ei ole omaa sääntöä.',
            'strategy'          => 'Strategia',
            'select-strategy'   => 'Valitse strategia',
            'strategy-info'     => 'Miten säilytettävä arvo valitaan. Tietojen vastuuhenkilöt voivat edelleen muuttaa jokaista valintaa yhdistämisnäkymässä.',
            'source-order'      => 'Lähteiden järjestys',
            'select-sources'    => 'Valitse lähdejärjestelmät',
            'source-order-info' => 'Luotettavin lähdejärjestelmä ensin. Pois jätetyt järjestelmät noudattavat omaa prioriteettiaan. Jätä tyhjäksi, jos haluat käyttää vain lähdejärjestelmien prioriteettia.',
            'sort-order'        => 'Järjestys',
            'save-btn'          => 'Tallenna säilytyssääntö',
        ],

        'targets' => [
            'default' => 'Oletus kaikille kentille',
            'label'   => 'Nimiö',
        ],

        'strategies' => [
            'source_priority' => 'Luotettavin lähde',
            'most_recent'     => 'Viimeksi päivitetty',
            'most_complete'   => 'Täydellisin',
            'keep_target'     => 'Säilytä jäävä tietue',
            'manual'          => 'Vastuuhenkilö päättää',
        ],

        'datagrid' => [
            'target'     => 'Koskee',
            'strategy'   => 'Strategia',
            'sort-order' => 'Järjestys',
            'edit'       => 'Muokkaa',
            'delete'     => 'Poista',
        ],

        'validation' => [
            'target-taken' => 'Tällä entiteettityypillä on jo säilytyssääntö tälle kohteelle.',
            'too-many'     => 'Entiteettityypillä voi olla enintään :count säilytyssääntöä.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Selaa alla olevia (:type)',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Ylin taso',
        ],

        'datagrid' => [
            'erase'         => 'Poista pysyvästi',
            'parent'        => 'Yläkohde',
            'top-level'     => 'Ylin taso',
            'children'      => 'Alakohteet',
            'view-children' => 'Näytä tämän alla olevat :count tietuetta',
        ],

        'erase' => [
            'btn'             => 'Poista pysyvästi',
            'confirm-title'   => 'Poistetaanko tämä tietue pysyvästi?',
            'confirm-message' => 'Tämän tietueen ja siihen yhdistettyjen kaksoiskappaleiden kaikki arvot, nimi ja koodi poistetaan, samoin niiden historia, muutospyynnöt ja laatuongelmat. Tietue säilyy poistettuna paikkamerkkinä, jotta sen lähdetunnisteet ja linkit toimivat edelleen. Tätä ei voi kumota.',
            'success'         => 'Tietue poistettu pysyvästi.',
            'not-retired'     => 'Vain käytöstä poistettu tietue voidaan poistaa. Poista se ensin käytöstä.',
        ],

        'mass-retire' => [
            'success' => ':count tietue(tta) poistettu käytöstä.',
            'pending' => ':count käytöstä poisto(a) lähetetty hyväksyttäväksi.',
            'skipped' => ':count tietue(tta) ohitettu, koska ne eivät ole luonnoksia tai aktiivisia tai joku muu on muuttanut niitä.',
            'none'    => 'Yhtäkään valituista tietueista ei voitu poistaa käytöstä.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Hyväksyntä tarvitaan: muutospyyntö #:id',
            'description'           => ':requester lähetti kohteeseen :type muutoksen ":operation" hyväksyttäväksesi.',
            'description-automated' => 'Automaattinen muutos ":operation" kohteeseen :type odottaa hyväksyntääsi.',
        ],
    ],

    'access' => [
        'menu'           => 'Tietojen käyttöoikeudet',
        'acl'            => [
            'index'  => 'Tietojen käyttöoikeudet',
            'create' => 'Luo',
            'edit'   => 'Muokkaa',
            'delete' => 'Poista',
        ],
        'principals' => [
            'role' => 'Rooli',
            'user' => 'Käyttäjä',
        ],
        'actions' => [
            'view'    => 'Näytä',
            'edit'    => 'Muokkaa',
            'approve' => 'Hyväksy',
        ],
        'index' => [
            'title'      => 'Tietojen käyttöoikeudet',
            'create-btn' => 'Lisää myöntö',
            'info'       => 'Myönnöt määräävät, mihin rajattujen entiteettityyppien tietueisiin kukin rooli tai käyttäjä pääsee: valittuun tietueeseen ja kaikkeen sen alapuolella. Käyttäjän roolin kautta tuleva pääsy ja käyttäjälle myönnetyt oikeudet lasketaan yhteen.',
        ],
        'create' => [
            'title' => 'Lisää tietojen käyttöoikeuden myöntö',
        ],
        'edit' => [
            'title' => 'Muokkaa tietojen käyttöoikeuden myöntöä',
        ],
        'form' => [
            'save-btn'         => 'Tallenna myöntö',
            'principal'        => 'Myönnetty kohteelle',
            'principal-info'   => 'Roolin myöntö koskee kaikkia käyttäjiä, joilla on kyseinen rooli. Myönnä yksittäiselle käyttäjälle poikkeuksena.',
            'principal-type'   => 'Myönnä kohteelle',
            'role'             => 'Rooli',
            'user'             => 'Käyttäjä',
            'scope'            => 'Laajuus',
            'scope-info'       => 'Myöntö kattaa valitun tietueen ja kaikki sen alapuolella olevat tietueet hierarkiassa.',
            'root'             => 'Alipuun juuri',
            'root-info'        => 'Esimerkiksi toimipaikka tai tehdas. Sen alapuoliset tietueet perivät myönnön.',
            'entity-type'      => 'Entiteettityyppi',
            'entity-type-info' => 'Rajaa myöntö yhteen rajattuun entiteettityyppiin tai jätä tyhjäksi kaikkia rajattuja tyyppejä varten.',
            'any-entity-type'  => 'Kaikki rajatut entiteettityypit',
            'actions'          => 'Toiminnot',
            'actions-info'     => 'Näytä lukee tietueita, Muokkaa muuttaa, poistaa käytöstä, yhdistää ja luo tietueita juuren alapuolella, Hyväksy ratkaisee pidätetyt muutokset. Muokkaa ja Hyväksy sisältävät Näytä-oikeuden.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tyyppi',
            'principal'         => 'Myönnetty kohteelle',
            'missing-principal' => 'Poistettu',
            'entity-type'       => 'Entiteettityyppi',
            'root'              => 'Alipuun juuri',
            'actions'           => 'Toiminnot',
            'edit'              => 'Muokkaa',
            'delete'            => 'Poista',
        ],
        'role-card' => [
            'title'  => 'Päätietojen käyttöoikeudet',
            'info'   => 'Mihin rajattujen entiteettityyppien tietueisiin tämän roolin käyttäjät pääsevät. Kukin myöntö kattaa juuritietueensa ja kaiken sen alapuolella.',
            'empty'  => 'Tällä roolilla ei ole vielä päätietojen käyttöoikeuksien myöntöjä.',
            'remove' => 'Poista',
            'add'    => 'Lisää myöntö',
        ],
        'entity-type' => [
            'is-scoped'      => 'Rajoita pääsyä alipuun mukaan',
            'is-scoped-info' => 'Kun päällä, käyttäjät pääsevät vain tämän tyypin tietueisiin niissä alipuissa, jotka on myönnetty heidän roolilleen tai heille Tietojen käyttöoikeudet -näkymässä. Jätä pois päältä jaetulle viitetiedolle.',
        ],
        'validation' => [
            'record-not-found'    => 'Valitse alipuun juureksi tietue, jonka voit nähdä.',
            'parent-out-of-scope' => 'Valitse yläkohde alipuusta, jossa sinulla on muokkausoikeus.',
        ],
        'create-success' => 'Tietojen käyttöoikeuden myöntö lisätty.',
        'update-success' => 'Tietojen käyttöoikeuden myöntö päivitetty.',
        'delete-success' => 'Tietojen käyttöoikeuden myöntö poistettu.',
    ],

    'install' => [
        'migrating' => 'Suoritetaan perustietojen migraatioita.',
        'demo'      => 'Asennetaan valmistuksen demotiedot.',
        'done'      => 'Perustietojen hallinta on asennettu. Aktiiviset moduulit: :modules.',
    ],
];
