<?php

return [
    'menu' => [
        'master-data' => 'Stamgegevens',
        'icons'       => 'Pictogrammen',
    ],

    'acl' => [
        'master-data' => 'Stamgegevens',
        'icons'       => [
            'index'  => 'Pictogrammen',
            'create' => 'Aanmaken',
            'edit'   => 'Bewerken',
            'delete' => 'Verwijderen',
        ],

        'records' => [
            'erase' => 'Definitief wissen',
        ],
    ],

    'dashboard' => [
        'title'      => 'Stamgegevens',
        'inside'     => 'Binnen: :types',
        'top-level'  => 'Hoogste niveau',
        'standalone' => 'Zelfstandig',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Stamgegevens',
            'records'     => 'Records',
            'upsert'      => 'Invoegen of bijwerken op bronsysteem-ID',
        ],

        'validation-failed'     => 'Validatie mislukt.',
        'change-held'           => 'De wijziging wacht op goedkeuring.',
        'xref-not-found'        => 'Geen record komt overeen met deze externe id.',
        'entity-type-not-found' => 'Het entiteitstype is niet gevonden.',
        'record-not-found'      => 'Het record is niet gevonden.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Entiteitstype',
            'source-system'       => 'Bronsysteem',
            'code-list'           => 'Codelijst',
            'entity-type-info'    => 'Het entiteitstype waarvan het bestand de records bevat.',
            'source-system-info'  => 'Het systeem waar het bestand vandaan komt. De externe ID\'s worden gelezen uit de kolom external_id.',
            'code-list-info'      => 'De lijst waarin wordt geïmporteerd. Codes worden in de conceptversie geschreven, die je apart publiceert.',
            'mapping-info'        => 'Eén regel per hernoemde kolom: bestandskolom=veldcode, of veldcode-locale voor een veld per locale, bijv. Asset Num=external_id en Description=name. Kolommen zonder regel worden onder hun eigen naam gelezen.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Kies een actief entiteitstype waarvan je records mag aanmaken of bewerken.',
            'source-system-invalid'              => 'Kies een actief bronsysteem.',
            'code-list-invalid'                  => 'Kies een bestaande codelijst die je mag bewerken.',
            'external-id-without-source-system'  => 'Het bestand heeft een kolom met externe ID\'s (:column). Kies het bronsysteem waar het vandaan komt, of verwijder de kolom.',
        ],

        'schedule' => [
            'job-not-found'  => 'Geen importtaak heeft de code :code.',
            'not-masterdata' => 'De taak :code is geen import van stamgegevens.',
            'no-user'        => 'Geef met --user of MASTERDATA_IMPORT_USER de beheerder op onder wie de import draait.',
            'user-not-found' => 'Geen actieve beheerder heeft het e-mailadres :email.',
            'file-not-found' => 'Het bestand :path is niet gevonden op de schijf :disk.',
            'nothing'        => 'Er wacht geen bestand in :path op de schijf :disk.',
            'queued'         => 'Bestand :file in de wachtrij gezet voor de taak :code, gevolgd als #:id.',
            'imported'       => 'Bestand :file geïmporteerd voor de taak :code, gevolgd als #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Productiedemo gereed: :types entiteitstypen, :systems bronsystemen, :records records in een locatieboom, :xrefs kruisverwijzingen, :lists codelijsten, :fields velden, :aliases aliassen.',
        'invalid-template' => 'De sjabloon :name kon niet worden gelezen of heeft een niet-ondersteunde formaatversie.',
    ],

    'platform-module' => [
        'name' => 'Stamgegevens (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Verwijderde entiteitstypen: :count.',
        'mass-delete-skipped' => 'Niet verwijderd omdat ze nog records bevatten: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Bovenliggende typen',
            'records-count'        => 'Records',
            'fields-count'         => 'Velden',
            'updated-at'           => 'Bijgewerkt',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sorteervolgorde',
        ],

        'edit' => [
            'schema-version' => 'Schema v:version',
        ],

        'form' => [
            'basics'                    => 'Basisgegevens',
            'identity'                  => 'Identiteit',
            'hierarchy'                 => 'Hiërarchie',
            'governance'                => 'Beheer',
            'quality-matching'          => 'Kwaliteit en koppeling',
            'matching-info'             => 'Binnenkomende rijen uit bronsystemen worden in de matchinbox gekoppeld aan bestaande records.',
            'auto-match-threshold'      => 'Drempel voor automatisch matchen',
            'auto-match-off'            => 'Uit',
            'auto-match-threshold-info' => 'Laat leeg om elke rij te beoordelen. Van 0,5 tot 1: een rij waarvan de beste suggestie minstens deze score haalt, zonder andere even hoge suggestie, wordt automatisch geaccepteerd. Bij typen die goedkeuring vereisen wacht de koppeling nog steeds op goedkeuring.',
            'match-on-write'            => 'Bij opslaan op dubbele records controleren',
            'match-on-write-info'       => 'Elk opgeslagen record wordt op de achtergrond met de matchregels vergeleken met de andere records. Schakel uit om dubbele records alleen bij kwaliteitsscans te vinden.',
            'manage-icons'              => 'Pictogrammen beheren',
            'sort-order'                => 'Sorteervolgorde',
            'sort-order-info'           => 'Lagere nummers staan bovenaan, van 0 tot 9999; typen met hetzelfde nummer worden op code gesorteerd.',
        ],

        'quick-create' => [
            'code-info'             => 'Kleine letters, cijfers en underscores, beginnend met een letter. Kan na opslaan niet meer worden gewijzigd.',
            'code-pattern-info'     => 'Gebruik {seq} of {seq:6} voor het volgnummer en {TYPE} voor de typecode, bijvoorbeeld {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Records komen uit meerdere bronsystemen en worden gematcht en samengevoegd tot één golden record.',
                'centralized'   => 'Records worden hier aangemaakt en beheerd en daarna gepubliceerd naar de systemen die ze gebruiken.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Matchregel op :target',
        ],

        'show' => [
            'auto-matched' => 'Automatisch gematcht',
            'auto-pending' => 'Automatisch gematcht; de koppeling wacht op goedkeuring.',
        ],

        'datagrid' => [
            'decision' => 'Beslissing',
            'auto'     => 'Automatisch gematcht',
            'manual'   => 'Handmatig',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Gebruikt door',
            'empty'        => 'Geen exporttaak publiceert records van dit type en deze status.',
            'job'          => 'Exporttaak',
            'format'       => 'Formaat',
            'mode'         => 'Modus',
            'full'         => 'Volledig',
            'delta'        => 'Alleen wijzigingen',
            'last-run-at'  => 'Laatste voltooide run',
            'no-run'       => 'Nooit uitgevoerd',
            'record-state' => 'Dit record',
            'states'       => [
                'never_run'  => 'Nog niet geëxporteerd',
                'pending'    => 'Gewijzigd sinds laatste run',
                'included'   => 'Opgenomen in laatste run',
                'up_to_date' => 'Actueel',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Pictogram succesvol aangemaakt.',
        'update-success' => 'Pictogram succesvol bijgewerkt.',
        'delete-success' => 'Pictogram succesvol verwijderd.',
        'delete-in-use'  => '{1} Dit pictogram wordt door één entiteitstype gebruikt en kan niet worden verwijderd.|[2,*] Dit pictogram wordt door :count entiteitstypen gebruikt en kan niet worden verwijderd.',
        'index'          => [
            'title'      => 'Pictogrammen',
            'create-btn' => 'Pictogram toevoegen',
        ],

        'create' => [
            'title'    => 'Pictogram toevoegen',
            'save-btn' => 'Pictogram opslaan',
        ],

        'edit' => [
            'title'    => 'Pictogram bewerken',
            'save-btn' => 'Pictogram opslaan',
        ],

        'form' => [
            'code'      => 'Code',
            'name'      => 'Naam',
            'file'      => 'Pictogrambestand',
            'file-info' => 'SVG, PNG of WebP, tot :size KB. Gebruik één vorm op een transparante achtergrond: het neemt de themakleur over.',
            'preview'   => 'Voorbeeld',
            'usage'     => '{0} Nog geen enkel entiteitstype gebruikt dit pictogram.|{1} Gebruikt door één entiteitstype.|[2,*] Gebruikt door :count entiteitstypen.',
        ],

        'library' => [
            'barcode'    => 'Barcode',
            'boxes'      => 'Materialen',
            'briefcase'  => 'Leverancier',
            'building_2' => 'Bedrijf',
            'cpu'        => 'Activa',
            'factory'    => 'Fabriek',
            'file_text'  => 'Contract',
            'globe'      => 'Land',
            'handshake'  => 'Partner',
            'landmark'   => 'Rechtspersoon',
            'map_pin'    => 'Locatie',
            'network'    => 'Hiërarchie',
            'package'    => 'Product',
            'store'      => 'Winkel',
            'tag'        => 'Categorie',
            'truck'      => 'Toeleverancier',
            'user_round' => 'Contactpersoon',
            'users'      => 'Klant',
            'warehouse'  => 'Magazijn',
            'wrench'     => 'Apparatuur',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Pictogram',
            'code'    => 'Code',
            'name'    => 'Naam',
            'usage'   => 'Gebruikt door',
            'edit'    => 'Bewerken',
            'delete'  => 'Verwijderen',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Koppeling',
        'title'          => 'Matchregels',
        'info'           => 'Bepaal hoe records van dit type worden vergeleken om dubbele records te vinden en binnenkomende rijen te koppelen. Zonder regels worden de code en het label vergeleken.',
        'create-btn'     => 'Matchregel toevoegen',
        'create-success' => 'Matchregel succesvol aangemaakt.',
        'update-success' => 'Matchregel succesvol bijgewerkt.',
        'delete-success' => 'Matchregel succesvol verwijderd.',

        'create' => [
            'title' => 'Matchregel toevoegen',
        ],

        'edit' => [
            'title' => 'Matchregel bewerken',
        ],

        'form' => [
            'target'            => 'Vergelijken',
            'select-target'     => 'Selecteer wat u wilt vergelijken',
            'target-info'       => 'De recordcode, het label of een veld van dit entiteitstype.',
            'comparator'        => 'Vergelijkingsmethode',
            'select-comparator' => 'Selecteer een vergelijkingsmethode',
            'comparator-info'   => 'Hoe de twee waarden worden vergeleken, bijv. Vaag voor namen met typefouten of Numeriek voor bedragen.',
            'weight'            => 'Gewicht',
            'weight-info'       => 'Hoe zwaar deze regel meetelt, van 0,1 tot 100. De score is het gewogen gemiddelde van de regels waarvoor de binnenkomende rij een waarde heeft.',
            'tolerance'         => 'Tolerantie',
            'tolerance-info'    => 'Het grootste verschil tussen twee getallen dat nog als vergelijkbaar telt. Laat leeg om alleen gelijke getallen te matchen.',
            'days'              => 'Datumvenster (dagen)',
            'days-info'         => 'Het maximale aantal dagen dat twee datums uit elkaar mogen liggen om nog als vergelijkbaar te gelden. Laat leeg om alleen dezelfde dag te matchen.',
            'sort-order'        => 'Sorteervolgorde',
            'is-blocking'       => 'Blokkeersleutel',
            'is-blocking-info'  => 'Zoekt ook naar records met exact deze waarde, zelfs als hun code en label verschillen. Velden moeten doorzoekbaar zijn.',
            'save-btn'          => 'Matchregel opslaan',
        ],

        'targets' => [
            'code'  => 'Code',
            'label' => 'Label',
        ],

        'comparators' => [
            'exact'    => 'Exact',
            'fuzzy'    => 'Vaag',
            'token'    => 'Dezelfde woorden',
            'phonetic' => 'Klinkt hetzelfde',
            'numeric'  => 'Numeriek',
            'date'     => 'Datum',
        ],

        'datagrid' => [
            'target'     => 'Vergelijken',
            'comparator' => 'Vergelijkingsmethode',
            'weight'     => 'Gewicht',
            'blocking'   => 'Blokkeersleutel',
            'sort-order' => 'Sorteervolgorde',
            'yes'        => 'Ja',
            'no'         => 'Nee',
            'edit'       => 'Bewerken',
            'delete'     => 'Verwijderen',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Alleen de code, het label of een doorzoekbaar veld kan een blokkeersleutel zijn.',
            'too-many'                => 'Een entiteitstype kan maximaal :count matchregels hebben.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Voorrang',
        'title'          => 'Voorrangsregels',
        'info'           => 'Bepaal welke waarde de golden record behoudt wanneer twee records van dit type worden samengevoegd. Zonder regels behoudt het overblijvende record zijn waarde, tenzij die leeg is.',
        'create-btn'     => 'Voorrangsregel toevoegen',
        'create-success' => 'Voorrangsregel succesvol aangemaakt.',
        'update-success' => 'Voorrangsregel succesvol bijgewerkt.',
        'delete-success' => 'Voorrangsregel succesvol verwijderd.',

        'create' => [
            'title' => 'Voorrangsregel toevoegen',
        ],

        'edit' => [
            'title' => 'Voorrangsregel bewerken',
        ],

        'form' => [
            'target'            => 'Geldt voor',
            'select-target'     => 'Selecteer waarop de regel van toepassing is',
            'target-info'       => 'Een veld, het label van het record of elk veld zonder eigen regel.',
            'strategy'          => 'Strategie',
            'select-strategy'   => 'Selecteer een strategie',
            'strategy-info'     => 'Hoe de overblijvende waarde wordt gekozen. Datastewards kunnen elke keuze nog steeds wijzigen in het samenvoegscherm.',
            'source-order'      => 'Bronvolgorde',
            'select-sources'    => 'Selecteer bronsystemen',
            'source-order-info' => 'Het meest betrouwbare bronsysteem eerst. Weggelaten systemen volgen hun prioriteit. Laat leeg om alleen de prioriteit van de bronsystemen te gebruiken.',
            'sort-order'        => 'Sorteervolgorde',
            'save-btn'          => 'Voorrangsregel opslaan',
        ],

        'targets' => [
            'default' => 'Standaard voor alle velden',
            'label'   => 'Label',
        ],

        'strategies' => [
            'source_priority' => 'Meest betrouwbare bron',
            'most_recent'     => 'Meest recent bijgewerkt',
            'most_complete'   => 'Meest volledig',
            'keep_target'     => 'Overblijvend record behouden',
            'manual'          => 'Datasteward beslist',
        ],

        'datagrid' => [
            'target'     => 'Geldt voor',
            'strategy'   => 'Strategie',
            'sort-order' => 'Sorteervolgorde',
            'edit'       => 'Bewerken',
            'delete'     => 'Verwijderen',
        ],

        'validation' => [
            'target-taken' => 'Dit entiteitstype heeft al een voorrangsregel voor dit doel.',
            'too-many'     => 'Een entiteitstype kan maximaal :count voorrangsregels hebben.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Onderliggende :type bekijken',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Hoogste niveau',
        ],

        'datagrid' => [
            'erase'         => 'Definitief wissen',
            'parent'        => 'Bovenliggend',
            'top-level'     => 'Hoogste niveau',
            'children'      => 'Onderliggend',
            'view-children' => 'De :count records hieronder tonen',
        ],

        'erase' => [
            'btn'             => 'Definitief wissen',
            'confirm-title'   => 'Dit record definitief wissen?',
            'confirm-message' => 'Alle waarden, het label en de code van dit record en van de duplicaten die erin zijn samengevoegd worden verwijderd, samen met hun geschiedenis, wijzigingsverzoeken en kwaliteitsproblemen. Het record blijft bestaan als gewiste tijdelijke aanduiding, zodat de bron-ID\'s en koppelingen blijven werken. Dit kan niet ongedaan worden gemaakt.',
            'success'         => 'Record definitief gewist.',
            'not-retired'     => 'Alleen een buiten gebruik gesteld record kan worden gewist. Stel het eerst buiten gebruik.',
        ],

        'mass-retire' => [
            'success' => ':count record(s) buiten gebruik gesteld.',
            'pending' => ':count buitengebruikstelling(en) ter goedkeuring ingediend.',
            'skipped' => ':count record(s) overgeslagen omdat ze geen concept of actief zijn, of door iemand anders zijn gewijzigd.',
            'none'    => 'Geen van de geselecteerde records kon buiten gebruik worden gesteld.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Goedkeuring nodig: wijzigingsverzoek #:id',
            'description'           => ':requester heeft een ":operation"-wijziging op :type ter goedkeuring ingediend.',
            'description-automated' => 'Een automatische ":operation"-wijziging op :type wacht op je goedkeuring.',
        ],
    ],

    'access' => [
        'menu'           => 'Gegevenstoegang',
        'acl'            => [
            'index'  => 'Gegevenstoegang',
            'create' => 'Aanmaken',
            'edit'   => 'Bewerken',
            'delete' => 'Verwijderen',
        ],
        'principals' => [
            'role' => 'Rol',
            'user' => 'Gebruiker',
        ],
        'actions' => [
            'view'    => 'Bekijken',
            'edit'    => 'Bewerken',
            'approve' => 'Goedkeuren',
        ],
        'index' => [
            'title'      => 'Gegevenstoegang',
            'create-btn' => 'Machtiging toevoegen',
            'info'       => 'Machtigingen bepalen tot welke records van beperkte entiteitstypen elke rol of gebruiker toegang heeft: het gekozen record en alles eronder. Toegang via de rol van een gebruiker en via machtigingen aan de gebruiker worden bij elkaar opgeteld.',
        ],
        'create' => [
            'title' => 'Gegevenstoegangsmachtiging toevoegen',
        ],
        'edit' => [
            'title' => 'Gegevenstoegangsmachtiging bewerken',
        ],
        'form' => [
            'save-btn'         => 'Machtiging opslaan',
            'principal'        => 'Verleend aan',
            'principal-info'   => 'Een rolmachtiging geldt voor elke gebruiker met die rol. Verleen aan één gebruiker voor een uitzondering.',
            'principal-type'   => 'Verlenen aan',
            'role'             => 'Rol',
            'user'             => 'Gebruiker',
            'scope'            => 'Bereik',
            'scope-info'       => 'De machtiging omvat het gekozen record en elk record eronder in de hiërarchie.',
            'root'             => 'Wortel van de substructuur',
            'root-info'        => 'Bijvoorbeeld een vestiging of fabriek. Records eronder erven de machtiging.',
            'entity-type'      => 'Entiteitstype',
            'entity-type-info' => 'Beperk de machtiging tot één beperkt entiteitstype, of laat leeg voor alle beperkte typen.',
            'any-entity-type'  => 'Alle beperkte entiteitstypen',
            'actions'          => 'Acties',
            'actions-info'     => 'Bekijken leest records, Bewerken wijzigt, buiten gebruik stelt, voegt samen en maakt records onder de wortel, Goedkeuren beslist over vastgehouden wijzigingen. Bewerken en Goedkeuren omvatten Bekijken.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Type',
            'principal'         => 'Verleend aan',
            'missing-principal' => 'Verwijderd',
            'entity-type'       => 'Entiteitstype',
            'root'              => 'Wortel van de substructuur',
            'actions'           => 'Acties',
            'edit'              => 'Bewerken',
            'delete'            => 'Verwijderen',
        ],
        'role-card' => [
            'title'  => 'Toegang tot stamgegevens',
            'info'   => 'Tot welke records van beperkte entiteitstypen gebruikers met deze rol toegang hebben. Elke machtiging omvat het wortelrecord en alles eronder.',
            'empty'  => 'Deze rol heeft nog geen machtigingen voor toegang tot stamgegevens.',
            'remove' => 'Verwijderen',
            'add'    => 'Machtiging toevoegen',
        ],
        'entity-type' => [
            'is-scoped'      => 'Toegang beperken per substructuur',
            'is-scoped-info' => 'Indien ingeschakeld hebben gebruikers alleen toegang tot records van dit type binnen de substructuren die aan hun rol of aan henzelf zijn verleend op het scherm Gegevenstoegang. Laat uitgeschakeld voor gedeelde referentiegegevens.',
        ],
        'validation' => [
            'record-not-found'    => 'Kies een record dat u kunt zien als wortel van de substructuur.',
            'parent-out-of-scope' => 'Kies een bovenliggend record binnen een substructuur waar u bewerkingstoegang hebt.',
        ],
        'create-success' => 'Gegevenstoegangsmachtiging toegevoegd.',
        'update-success' => 'Gegevenstoegangsmachtiging bijgewerkt.',
        'delete-success' => 'Gegevenstoegangsmachtiging verwijderd.',
    ],
];
