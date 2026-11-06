<?php

return [
    'menu' => [
        'master-data' => 'Huvuddata',
        'icons'       => 'Ikoner',
    ],

    'acl' => [
        'master-data' => 'Huvuddata',
        'icons'       => [
            'index'  => 'Ikoner',
            'create' => 'Skapa',
            'edit'   => 'Redigera',
            'delete' => 'Ta bort',
        ],

        'records' => [
            'erase' => 'Radera permanent',
        ],
    ],

    'dashboard' => [
        'title'      => 'Huvuddata',
        'inside'     => 'Under: :types',
        'top-level'  => 'Översta nivån',
        'standalone' => 'Fristående',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Huvuddata',
            'records'     => 'Poster',
            'upsert'      => 'Infoga eller uppdatera efter källsystemets ID',
        ],

        'validation-failed'     => 'Valideringen misslyckades.',
        'change-held'           => 'Ändringen väntar på godkännande.',
        'xref-not-found'        => 'Ingen post matchar detta externa id.',
        'entity-type-not-found' => 'Entitetstypen hittades inte.',
        'record-not-found'      => 'Posten hittades inte.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Entitetstyp',
            'source-system'       => 'Källsystem',
            'code-list'           => 'Kodlista',
            'entity-type-info'    => 'Den entitetstyp vars poster filen innehåller.',
            'source-system-info'  => 'Systemet som filen kommer från. Dess externa id:n läses från kolumnen external_id.',
            'code-list-info'      => 'Listan som importeras till. Koderna skrivs till dess utkastversion, som du publicerar separat.',
            'mapping-info'        => 'En rad per omdöpt kolumn: filkolumn=fältkod, eller fältkod-språk för ett fält per språk, t.ex. Asset Num=external_id och Description=name. Kolumner utan rad läses under sitt eget namn.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Välj en aktiv entitetstyp vars poster du får skapa eller redigera.',
            'source-system-invalid'              => 'Välj ett aktivt källsystem.',
            'code-list-invalid'                  => 'Välj en befintlig kodlista som du får redigera.',
            'external-id-without-source-system'  => 'Filen har en kolumn med externa id:n (:column). Välj källsystemet den kommer från, eller ta bort kolumnen.',
        ],

        'schedule' => [
            'job-not-found'  => 'Inget importjobb har koden :code.',
            'not-masterdata' => 'Jobbet :code är inte en masterdataimport.',
            'no-user'        => 'Ange administratören som importen körs som med --user eller MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Ingen aktiv administratör har e-postadressen :email.',
            'file-not-found' => 'Filen :path hittades inte på disken :disk.',
            'nothing'        => 'Ingen fil väntar i :path på disken :disk.',
            'queued'         => 'Ställde :file i kö för jobbet :code, spåras som #:id.',
            'imported'       => 'Importerade :file för jobbet :code, spåras som #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Tillverkningsdemon är klar: :types entitetstyper, :systems källsystem, :records poster i ett anläggningsträd, :xrefs korsreferenser, :lists kodlistor, :fields fält, :aliases alias.',
        'invalid-template' => 'Mallen :name kunde inte läsas eller har en formatversion som inte stöds.',
    ],

    'platform-module' => [
        'name' => 'Masterdata (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Borttagna entitetstyper: :count.',
        'mass-delete-skipped' => 'Inte borttagna eftersom de fortfarande har poster: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Överordnade typer',
            'records-count'        => 'Poster',
            'fields-count'         => 'Fält',
            'updated-at'           => 'Uppdaterad',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sorteringsordning',
        ],

        'edit' => [
            'schema-version' => 'Schema v:version',
        ],

        'form' => [
            'basics'                    => 'Grundläggande',
            'identity'                  => 'Identitet',
            'hierarchy'                 => 'Hierarki',
            'governance'                => 'Styrning',
            'quality-matching'          => 'Kvalitet och matchning',
            'matching-info'             => 'Inkommande rader från källsystem matchas mot befintliga poster i matchningsinkorgen.',
            'auto-match-threshold'      => 'Tröskel för automatisk matchning',
            'auto-match-off'            => 'Av',
            'auto-match-threshold-info' => 'Lämna tomt för att granska varje rad. Från 0,5 till 1: en rad vars bästa förslag får minst detta värde, utan att något annat förslag är lika högt, godkänns automatiskt. Typer som kräver godkännande håller fortfarande kopplingen för godkännande.',
            'match-on-write'            => 'Sök efter dubbletter vid sparande',
            'match-on-write-info'       => 'Varje sparad post jämförs i bakgrunden med övriga poster med hjälp av matchningsreglerna. Stäng av för att bara hitta dubbletter vid kvalitetsskanningar.',
            'manage-icons'              => 'Hantera ikoner',
            'sort-order'                => 'Sorteringsordning',
            'sort-order-info'           => 'Lägre nummer visas först, från 0 till 9999; typer med samma nummer sorteras efter kod.',
        ],

        'quick-create' => [
            'code-info'             => 'Små bokstäver, siffror och understreck, som börjar med en bokstav. Kan inte ändras efter att den sparats.',
            'code-pattern-info'     => 'Använd {seq} eller {seq:6} för löpnumret och {TYPE} för typkoden, till exempel {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Poster kommer från flera källsystem och matchas och slås samman till en gyllene post.',
                'centralized'   => 'Poster skapas och underhålls här och publiceras sedan till de system som använder dem.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Matchningsregel för :target',
        ],

        'show' => [
            'auto-matched' => 'Automatiskt matchad',
            'auto-pending' => 'Matchad automatiskt; kopplingen väntar på godkännande.',
        ],

        'datagrid' => [
            'decision' => 'Beslut',
            'auto'     => 'Automatiskt matchad',
            'manual'   => 'Manuell',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Används av',
            'empty'        => 'Inget exportjobb publicerar poster av denna typ och status.',
            'job'          => 'Exportjobb',
            'format'       => 'Format',
            'mode'         => 'Läge',
            'full'         => 'Fullständig',
            'delta'        => 'Endast ändringar',
            'last-run-at'  => 'Senaste slutförda körning',
            'no-run'       => 'Aldrig körd',
            'record-state' => 'Denna post',
            'states'       => [
                'never_run'  => 'Inte exporterad än',
                'pending'    => 'Ändrad sedan senaste körningen',
                'included'   => 'Med i senaste körningen',
                'up_to_date' => 'Aktuell',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikonen skapades.',
        'update-success' => 'Ikonen uppdaterades.',
        'delete-success' => 'Ikonen togs bort.',
        'delete-in-use'  => '{1} Den här ikonen visas av en entitetstyp och kan inte tas bort.|[2,*] Den här ikonen visas av :count entitetstyper och kan inte tas bort.',
        'index'          => [
            'title'      => 'Ikoner',
            'create-btn' => 'Lägg till ikon',
        ],

        'create' => [
            'title'    => 'Lägg till ikon',
            'save-btn' => 'Spara ikon',
        ],

        'edit' => [
            'title'    => 'Redigera ikon',
            'save-btn' => 'Spara ikon',
        ],

        'form' => [
            'code'      => 'Kod',
            'name'      => 'Namn',
            'file'      => 'Ikonfil',
            'file-info' => 'SVG, PNG eller WebP, upp till :size KB. Använd en enda form på transparent bakgrund: den tar temats färg.',
            'preview'   => 'Förhandsgranskning',
            'usage'     => '{0} Ingen entitetstyp visar den här ikonen ännu.|{1} Visas av en entitetstyp.|[2,*] Visas av :count entitetstyper.',
        ],

        'library' => [
            'barcode'    => 'Streckkod',
            'boxes'      => 'Material',
            'briefcase'  => 'Leverantör av tjänster',
            'building_2' => 'Företag',
            'cpu'        => 'Tillgång',
            'factory'    => 'Fabrik',
            'file_text'  => 'Avtal',
            'globe'      => 'Land',
            'handshake'  => 'Partner',
            'landmark'   => 'Juridisk enhet',
            'map_pin'    => 'Plats',
            'network'    => 'Hierarki',
            'package'    => 'Produkt',
            'store'      => 'Butik',
            'tag'        => 'Kategori',
            'truck'      => 'Leverantör',
            'user_round' => 'Kontakt',
            'users'      => 'Kund',
            'warehouse'  => 'Lager',
            'wrench'     => 'Utrustning',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikon',
            'code'    => 'Kod',
            'name'    => 'Namn',
            'usage'   => 'Används av',
            'edit'    => 'Redigera',
            'delete'  => 'Ta bort',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Matchning',
        'title'          => 'Matchningsregler',
        'info'           => 'Bestäm hur poster av den här typen jämförs för att hitta dubbletter och matcha inkommande rader. Utan regler jämförs koden och etiketten.',
        'create-btn'     => 'Lägg till matchningsregel',
        'create-success' => 'Matchningsregeln skapades.',
        'update-success' => 'Matchningsregeln uppdaterades.',
        'delete-success' => 'Matchningsregeln togs bort.',

        'create' => [
            'title' => 'Lägg till matchningsregel',
        ],

        'edit' => [
            'title' => 'Redigera matchningsregel',
        ],

        'form' => [
            'target'            => 'Jämför',
            'select-target'     => 'Välj vad som ska jämföras',
            'target-info'       => 'Postens kod, dess etikett eller ett fält i den här entitetstypen.',
            'comparator'        => 'Jämförelsemetod',
            'select-comparator' => 'Välj en jämförelsemetod',
            'comparator-info'   => 'Hur de två värdena jämförs, t.ex. Ungefärlig för namn med stavfel eller Numerisk för belopp.',
            'weight'            => 'Vikt',
            'weight-info'       => 'Hur mycket regeln väger, från 0,1 till 100. Poängen är det viktade medelvärdet av de regler som den inkommande raden har ett värde för.',
            'tolerance'         => 'Tolerans',
            'tolerance-info'    => 'Den största skillnaden mellan två tal som fortfarande räknas som lika. Lämna tomt för att bara matcha lika tal.',
            'days'              => 'Datumfönster (dagar)',
            'days-info'         => 'Hur många dagar två datum högst får skilja sig åt och ändå räknas som lika. Lämna tomt för att bara matcha samma dag.',
            'sort-order'        => 'Sorteringsordning',
            'is-blocking'       => 'Blockeringsnyckel',
            'is-blocking-info'  => 'Slå även upp poster som har exakt detta värde, även när deras kod och etikett ser olika ut. Fälten måste vara sökbara.',
            'save-btn'          => 'Spara matchningsregel',
        ],

        'targets' => [
            'code'  => 'Kod',
            'label' => 'Etikett',
        ],

        'comparators' => [
            'exact'    => 'Exakt',
            'fuzzy'    => 'Ungefärlig',
            'token'    => 'Samma ord',
            'phonetic' => 'Låter likadant',
            'numeric'  => 'Numerisk',
            'date'     => 'Datum',
        ],

        'datagrid' => [
            'target'     => 'Jämför',
            'comparator' => 'Jämförelsemetod',
            'weight'     => 'Vikt',
            'blocking'   => 'Blockeringsnyckel',
            'sort-order' => 'Sorteringsordning',
            'yes'        => 'Ja',
            'no'         => 'Nej',
            'edit'       => 'Redigera',
            'delete'     => 'Ta bort',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Endast koden, etiketten eller ett sökbart fält kan vara en blockeringsnyckel.',
            'too-many'                => 'En entitetstyp kan ha högst :count matchningsregler.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Överlevnad',
        'title'          => 'Överlevnadsregler',
        'info'           => 'Bestäm vilket värde golden record behåller när två poster av den här typen slås ihop. Utan regler behåller den överlevande posten sitt värde om det inte är tomt.',
        'create-btn'     => 'Lägg till överlevnadsregel',
        'create-success' => 'Överlevnadsregeln skapades.',
        'update-success' => 'Överlevnadsregeln uppdaterades.',
        'delete-success' => 'Överlevnadsregeln togs bort.',

        'create' => [
            'title' => 'Lägg till överlevnadsregel',
        ],

        'edit' => [
            'title' => 'Redigera överlevnadsregel',
        ],

        'form' => [
            'target'            => 'Gäller för',
            'select-target'     => 'Välj vad regeln gäller för',
            'target-info'       => 'Ett fält, postens etikett eller alla fält utan egen regel.',
            'strategy'          => 'Strategi',
            'select-strategy'   => 'Välj en strategi',
            'strategy-info'     => 'Hur det överlevande värdet väljs. Dataansvariga kan fortfarande ändra varje val på sammanslagningsskärmen.',
            'source-order'      => 'Källordning',
            'select-sources'    => 'Välj källsystem',
            'source-order-info' => 'Det mest pålitliga källsystemet först. Utelämnade system följer sin prioritet. Lämna tomt för att bara använda källsystemens prioritet.',
            'sort-order'        => 'Sorteringsordning',
            'save-btn'          => 'Spara överlevnadsregel',
        ],

        'targets' => [
            'default' => 'Standard för alla fält',
            'label'   => 'Etikett',
        ],

        'strategies' => [
            'source_priority' => 'Mest pålitliga källa',
            'most_recent'     => 'Senast uppdaterad',
            'most_complete'   => 'Mest komplett',
            'keep_target'     => 'Behåll den överlevande',
            'manual'          => 'Dataansvarig avgör',
        ],

        'datagrid' => [
            'target'     => 'Gäller för',
            'strategy'   => 'Strategi',
            'sort-order' => 'Sorteringsordning',
            'edit'       => 'Redigera',
            'delete'     => 'Ta bort',
        ],

        'validation' => [
            'target-taken' => 'Den här entitetstypen har redan en överlevnadsregel för det här målet.',
            'too-many'     => 'En entitetstyp kan ha högst :count överlevnadsregler.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Bläddra bland :type nedanför',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Översta nivån',
        ],

        'datagrid' => [
            'erase'         => 'Radera permanent',
            'parent'        => 'Överordnad',
            'top-level'     => 'Översta nivån',
            'children'      => 'Underordnade',
            'view-children' => 'Visa de :count posterna under denna',
        ],

        'erase' => [
            'btn'             => 'Radera permanent',
            'confirm-title'   => 'Radera posten permanent?',
            'confirm-message' => 'Alla värden, etiketten och koden för den här posten och för dubbletterna som har sammanfogats med den tas bort, tillsammans med historik, ändringsförfrågningar och kvalitetsproblem. Posten finns kvar som en raderad platshållare så att käll-ID:n och länkar fortfarande fungerar. Det går inte att ångra.',
            'success'         => 'Posten har raderats permanent.',
            'not-retired'     => 'Endast en avvecklad post kan raderas. Avveckla den först.',
        ],

        'mass-retire' => [
            'success' => ':count post(er) avvecklade.',
            'pending' => ':count avveckling(ar) skickade för godkännande.',
            'skipped' => ':count post(er) hoppades över eftersom de inte är utkast eller aktiva, eller har ändrats av någon annan.',
            'none'    => 'Ingen av de valda posterna kunde avvecklas.',
        ],
    ],

    'quality-dashboard' => [
        'menu'     => 'Kvalitetsöversikt',
        'acl'      => 'Kvalitetsöversikt',
        'index'    => [
            'title'         => 'Översikt över datakvalitet',
            'info'          => 'Dagliga ögonblicksbilder av fullständighet, giltighet och dubbletter per entitetstyp, med det arbete som väntar på varje dataförvaltare.',
            'empty'         => 'Det finns inga entitetstyper att rapportera om.',
            'scorecards'    => 'Resultatkort',
            'no-snapshot'   => 'Ingen ögonblicksbild har tagits ännu.',
            'not-available' => 'e/t',
            'percent'       => ':value %',
            'delta'         => ':value p',
            'records'       => 'Poster: :count',
            'open-tasks'    => 'Öppna uppgifter: :count',
            'snapshot-date' => 'Ögonblicksbild från :date',
            'trend'         => 'Utveckling: :type',
            'trend-empty'   => 'Inga ögonblicksbilder i detta intervall ännu.',
            'chart-label'   => 'Utveckling av datakvaliteten för :type från :from till :to',
            'workload'      => 'Dataförvaltarnas arbetsbelastning',
        ],
        'ranges' => [
            '7'  => 'Senaste 7 dagarna',
            '30' => 'Senaste 30 dagarna',
            '90' => 'Senaste 90 dagarna',
        ],
        'metrics' => [
            'completeness_avg' => 'Fullständighet',
            'validity_rate'    => 'Giltighet',
            'duplicate_rate'   => 'Dubbletter',
        ],
        'workload' => [
            'steward'          => 'Dataförvaltare',
            'change-requests'  => 'Öppna ändringsbegäranden',
            'match-candidates' => 'Öppna matchningskandidater',
            'unassigned'       => 'Ingen dataägare',
            'empty'            => 'Inga öppna uppgifter för dataförvaltare.',
            'buckets'          => [
                'day'   => 'Under 1 dag',
                'week'  => '1 till 7 dagar',
                'month' => '7 till 30 dagar',
                'older' => 'Över 30 dagar',
            ],
        ],
        'snapshot' => [
            'queued' => 'Lagring av kvalitetsögonblicksbild har köats.',
            'done'   => 'Kvalitetsögonblicksbilder har lagrats för :count entitetstyper.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Godkännande krävs: ändringsbegäran #:id',
            'description'           => ':requester har skickat en ":operation"-ändring av :type för ditt godkännande.',
            'description-automated' => 'En automatisk ":operation"-ändring av :type väntar på ditt godkännande.',
        ],
    ],

    'access' => [
        'menu'           => 'Dataåtkomst',
        'acl'            => [
            'index'  => 'Dataåtkomst',
            'create' => 'Skapa',
            'edit'   => 'Redigera',
            'delete' => 'Ta bort',
        ],
        'principals' => [
            'role' => 'Roll',
            'user' => 'Användare',
        ],
        'actions' => [
            'view'    => 'Visa',
            'edit'    => 'Redigera',
            'approve' => 'Godkänn',
        ],
        'index' => [
            'title'      => 'Dataåtkomst',
            'create-btn' => 'Lägg till behörighet',
            'info'       => 'Behörigheter avgör vilka poster av begränsade entitetstyper varje roll eller användare når: den valda posten och allt under den. Åtkomst via en användares roll och via behörigheter som getts till användaren läggs ihop.',
        ],
        'create' => [
            'title' => 'Lägg till dataåtkomstbehörighet',
        ],
        'edit' => [
            'title' => 'Redigera dataåtkomstbehörighet',
        ],
        'form' => [
            'save-btn'         => 'Spara behörighet',
            'principal'        => 'Beviljad till',
            'principal-info'   => 'En rollbehörighet gäller alla användare med den rollen. Bevilja till en enskild användare för ett undantag.',
            'principal-type'   => 'Bevilja till',
            'role'             => 'Roll',
            'user'             => 'Användare',
            'scope'            => 'Omfång',
            'scope-info'       => 'Behörigheten omfattar den valda posten och varje post under den i hierarkin.',
            'root'             => 'Rot för underträd',
            'root-info'        => 'Till exempel en anläggning eller en fabrik. Poster under den ärver behörigheten.',
            'entity-type'      => 'Entitetstyp',
            'entity-type-info' => 'Begränsa behörigheten till en begränsad entitetstyp, eller lämna tomt för alla begränsade typer.',
            'any-entity-type'  => 'Alla begränsade entitetstyper',
            'actions'          => 'Åtgärder',
            'actions-info'     => 'Visa läser poster, Redigera ändrar, avvecklar, slår ihop och skapar poster under roten, Godkänn avgör uppehållna ändringar. Redigera och Godkänn inkluderar Visa.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Typ',
            'principal'         => 'Beviljad till',
            'missing-principal' => 'Borttagen',
            'entity-type'       => 'Entitetstyp',
            'root'              => 'Rot för underträd',
            'actions'           => 'Åtgärder',
            'edit'              => 'Redigera',
            'delete'            => 'Ta bort',
        ],
        'role-card' => [
            'title'  => 'Åtkomst till huvuddata',
            'info'   => 'Vilka poster av begränsade entitetstyper användare med den här rollen når. Varje behörighet omfattar sin rotpost och allt under den.',
            'empty'  => 'Den här rollen har inga behörigheter för huvuddataåtkomst än.',
            'remove' => 'Ta bort',
            'add'    => 'Lägg till en behörighet',
        ],
        'entity-type' => [
            'is-scoped'      => 'Begränsa åtkomst efter underträd',
            'is-scoped-info' => 'När det är på når användare bara poster av den här typen inom de underträd som har beviljats deras roll eller dem själva på skärmen Dataåtkomst. Lämna av för delad referensdata.',
        ],
        'validation' => [
            'record-not-found'    => 'Välj en post som du kan se som rot för underträdet.',
            'parent-out-of-scope' => 'Välj en överordnad post inom ett underträd där du har redigeringsåtkomst.',
        ],
        'create-success' => 'Dataåtkomstbehörigheten har lagts till.',
        'update-success' => 'Dataåtkomstbehörigheten har uppdaterats.',
        'delete-success' => 'Dataåtkomstbehörigheten har tagits bort.',
    ],

    'install' => [
        'migrating' => 'Kör migreringarna för huvuddata.',
        'demo'      => 'Installerar demodata för tillverkning.',
        'done'      => 'Hantering av huvuddata är installerad. Aktiva moduler: :modules.',
    ],
];
