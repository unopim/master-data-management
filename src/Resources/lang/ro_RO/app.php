<?php

return [
    'menu' => [
        'master-data' => 'Date de bază',
        'icons'       => 'Pictograme',
    ],

    'acl' => [
        'master-data' => 'Date de bază',
        'icons'       => [
            'index'  => 'Pictograme',
            'create' => 'Creează',
            'edit'   => 'Editează',
            'delete' => 'Șterge',
        ],

        'records' => [
            'erase' => 'Șterge definitiv',
        ],
    ],

    'dashboard' => [
        'title'      => 'Date de bază',
        'inside'     => 'În cadrul: :types',
        'top-level'  => 'Nivel superior',
        'standalone' => 'Independent',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Date de referință',
            'records'     => 'Înregistrări',
            'upsert'      => 'Inserează sau actualizează după ID-ul sistemului sursă',
        ],

        'validation-failed'     => 'Validarea a eșuat.',
        'change-held'           => 'Modificarea așteaptă aprobarea.',
        'xref-not-found'        => 'Nicio înregistrare nu corespunde acestui ID extern.',
        'entity-type-not-found' => 'Tipul de entitate nu a fost găsit.',
        'record-not-found'      => 'Înregistrarea nu a fost găsită.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tip de entitate',
            'source-system'       => 'Sistem sursă',
            'code-list'           => 'Listă de coduri',
            'entity-type-info'    => 'Tipul de entitate ale cărui înregistrări le conține fișierul.',
            'source-system-info'  => 'Sistemul din care provine fișierul. ID-urile sale externe sunt citite din coloana external_id.',
            'code-list-info'      => 'Lista în care se importă. Codurile sunt scrise în versiunea sa ciornă, pe care o publicați separat.',
            'mapping-info'        => 'O linie pentru fiecare coloană redenumită: coloana fișierului=codul câmpului sau codul câmpului-limbă pentru un câmp pe limbă, de ex. Asset Num=external_id și Description=name. Coloanele fără linie sunt citite după propriul nume.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Alegeți un tip de entitate activ ale cărui înregistrări le puteți crea sau edita.',
            'source-system-invalid'              => 'Alegeți un sistem sursă activ.',
            'code-list-invalid'                  => 'Alegeți o listă de coduri existentă pe care o puteți edita.',
            'external-id-without-source-system'  => 'Fișierul are o coloană de ID-uri externe (:column). Alegeți sistemul sursă din care provine sau eliminați coloana.',
        ],

        'schedule' => [
            'job-not-found'  => 'Niciun job de import nu are codul :code.',
            'not-masterdata' => 'Jobul :code nu este un import de date master.',
            'no-user'        => 'Indicați administratorul sub care rulează importul cu --user sau MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Niciun administrator activ nu are adresa de e-mail :email.',
            'file-not-found' => 'Fișierul :path nu a fost găsit pe discul :disk.',
            'nothing'        => 'Niciun fișier nu așteaptă în :path pe discul :disk.',
            'queued'         => 'Fișierul :file a fost pus în coadă pentru jobul :code, urmărit ca #:id.',
            'imported'       => 'Fișierul :file a fost importat pentru jobul :code, urmărit ca #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demonstrația de producție este gata: :types tipuri de entități, :systems sisteme sursă, :records înregistrări într-un arbore de locații, :xrefs referințe încrucișate, :lists liste de coduri, :fields câmpuri, :aliases aliasuri.',
        'invalid-template' => 'Șablonul :name nu a putut fi citit sau are o versiune de format neacceptată.',
    ],

    'platform-module' => [
        'name' => 'Date de bază (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipuri de entitate șterse: :count.',
        'mass-delete-skipped' => 'Nu au fost șterse deoarece încă au înregistrări: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipuri părinte',
            'records-count'        => 'Înregistrări',
            'fields-count'         => 'Câmpuri',
            'updated-at'           => 'Actualizat',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordine de afișare',
        ],

        'edit' => [
            'schema-version' => 'Schemă v:version',
        ],

        'form' => [
            'basics'                    => 'Informații de bază',
            'identity'                  => 'Identitate',
            'hierarchy'                 => 'Ierarhie',
            'governance'                => 'Guvernanță',
            'quality-matching'          => 'Calitate și potrivire',
            'matching-info'             => 'Rândurile primite din sistemele sursă sunt potrivite cu înregistrările existente în căsuța de potriviri.',
            'auto-match-threshold'      => 'Prag de potrivire automată',
            'auto-match-off'            => 'Dezactivat',
            'auto-match-threshold-info' => 'Lăsați gol pentru a verifica fiecare rând. De la 0,5 la 1: un rând a cărui cea mai bună sugestie atinge cel puțin această valoare, fără o altă sugestie la fel de mare, este acceptat automat. Tipurile care necesită aprobare păstrează în continuare legătura în așteptarea aprobării.',
            'match-on-write'            => 'Verifică duplicatele la salvare',
            'match-on-write-info'       => 'Fiecare înregistrare salvată este comparată în fundal cu celelalte înregistrări, folosind regulile de potrivire. Dezactivați pentru a găsi duplicate doar în scanările de calitate.',
            'manage-icons'              => 'Gestionează pictogramele',
            'sort-order'                => 'Ordine de afișare',
            'sort-order-info'           => 'Numerele mai mici apar primele, de la 0 la 9999; tipurile cu același număr sunt ordonate după cod.',
        ],

        'quick-create' => [
            'code-info'             => 'Litere mici, cifre și caractere de subliniere, începând cu o literă. Nu poate fi modificat după salvare.',
            'code-pattern-info'     => 'Folosiți {seq} sau {seq:6} pentru numărul de ordine și {TYPE} pentru codul tipului, de exemplu {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Înregistrările provin din mai multe sisteme sursă și sunt potrivite și combinate într-o singură înregistrare de referință.',
                'centralized'   => 'Înregistrările sunt create și întreținute aici, apoi publicate în sistemele care le folosesc.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regulă de potrivire pentru :target',
        ],

        'show' => [
            'auto-matched' => 'Potrivit automat',
            'auto-pending' => 'Potrivit automat; legătura așteaptă aprobarea.',
        ],

        'datagrid' => [
            'decision' => 'Decizie',
            'auto'     => 'Potrivit automat',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Folosit de',
            'empty'        => 'Nicio sarcină de export nu publică înregistrări de acest tip și stare.',
            'job'          => 'Sarcină de export',
            'format'       => 'Format',
            'mode'         => 'Mod',
            'full'         => 'Complet',
            'delta'        => 'Doar modificări',
            'last-run-at'  => 'Ultima rulare finalizată',
            'no-run'       => 'Nu a rulat niciodată',
            'record-state' => 'Această înregistrare',
            'states'       => [
                'never_run'  => 'Neexportat încă',
                'pending'    => 'Modificat de la ultima rulare',
                'included'   => 'Inclus în ultima rulare',
                'up_to_date' => 'Actualizat',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Pictograma a fost creată cu succes.',
        'update-success' => 'Pictograma a fost actualizată cu succes.',
        'delete-success' => 'Pictograma a fost ștearsă cu succes.',
        'delete-in-use'  => '{1} Această pictogramă este afișată de un tip de entitate și nu poate fi ștearsă.|[2,*] Această pictogramă este afișată de :count tipuri de entitate și nu poate fi ștearsă.',
        'index'          => [
            'title'      => 'Pictograme',
            'create-btn' => 'Adaugă pictogramă',
        ],

        'create' => [
            'title'    => 'Adaugă pictogramă',
            'save-btn' => 'Salvează pictograma',
        ],

        'edit' => [
            'title'    => 'Editează pictograma',
            'save-btn' => 'Salvează pictograma',
        ],

        'form' => [
            'code'      => 'Cod',
            'name'      => 'Nume',
            'file'      => 'Fișier pictogramă',
            'file-info' => 'SVG, PNG sau WebP, până la :size KB. Folosește o singură formă pe fundal transparent: preia culoarea temei.',
            'preview'   => 'Previzualizare',
            'usage'     => '{0} Niciun tip de entitate nu afișează încă această pictogramă.|{1} Afișată de un tip de entitate.|[2,*] Afișată de :count tipuri de entitate.',
        ],

        'library' => [
            'barcode'    => 'Cod de bare',
            'boxes'      => 'Materiale',
            'briefcase'  => 'Prestator de servicii',
            'building_2' => 'Companie',
            'cpu'        => 'Activ',
            'factory'    => 'Fabrică',
            'file_text'  => 'Contract',
            'globe'      => 'Țară',
            'handshake'  => 'Partener',
            'landmark'   => 'Entitate juridică',
            'map_pin'    => 'Locație',
            'network'    => 'Ierarhie',
            'package'    => 'Produs',
            'store'      => 'Magazin',
            'tag'        => 'Categorie',
            'truck'      => 'Furnizor',
            'user_round' => 'Contact',
            'users'      => 'Client',
            'warehouse'  => 'Depozit',
            'wrench'     => 'Echipament',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Pictogramă',
            'code'    => 'Cod',
            'name'    => 'Nume',
            'usage'   => 'Folosită de',
            'edit'    => 'Editează',
            'delete'  => 'Șterge',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Potrivire',
        'title'          => 'Reguli de potrivire',
        'info'           => 'Stabiliți cum sunt comparate înregistrările de acest tip pentru a găsi duplicate și a potrivi rândurile primite. Fără reguli, se compară codul și eticheta.',
        'create-btn'     => 'Adaugă regulă de potrivire',
        'create-success' => 'Regula de potrivire a fost creată cu succes.',
        'update-success' => 'Regula de potrivire a fost actualizată cu succes.',
        'delete-success' => 'Regula de potrivire a fost ștearsă cu succes.',

        'create' => [
            'title' => 'Adaugă regulă de potrivire',
        ],

        'edit' => [
            'title' => 'Editează regula de potrivire',
        ],

        'form' => [
            'target'            => 'Compară',
            'select-target'     => 'Selectați ce se compară',
            'target-info'       => 'Codul înregistrării, eticheta sa sau un câmp al acestui tip de entitate.',
            'comparator'        => 'Comparator',
            'select-comparator' => 'Selectați un comparator',
            'comparator-info'   => 'Cum sunt comparate cele două valori, de ex. Aproximativ pentru nume cu greșeli de scriere sau Numeric pentru sume.',
            'weight'            => 'Pondere',
            'weight-info'       => 'Cât contează această regulă, de la 0,1 la 100. Scorul este media ponderată a regulilor pentru care rândul primit are o valoare.',
            'tolerance'         => 'Toleranță',
            'tolerance-info'    => 'Cea mai mare diferență dintre două numere care încă este considerată similară. Lăsați gol pentru a potrivi doar numere egale.',
            'days'              => 'Interval de date (zile)',
            'days-info'         => 'Numărul maxim de zile dintre două date pentru ca acestea să fie încă considerate similare. Lăsați gol pentru a potrivi doar aceeași zi.',
            'sort-order'        => 'Ordine de afișare',
            'is-blocking'       => 'Cheie de blocare',
            'is-blocking-info'  => 'Caută și înregistrările care au exact această valoare, chiar dacă au cod și etichetă diferite. Câmpurile trebuie să poată fi căutate.',
            'save-btn'          => 'Salvează regula de potrivire',
        ],

        'targets' => [
            'code'  => 'Cod',
            'label' => 'Etichetă',
        ],

        'comparators' => [
            'exact'    => 'Exact',
            'fuzzy'    => 'Aproximativ',
            'token'    => 'Aceleași cuvinte',
            'phonetic' => 'Sună la fel',
            'numeric'  => 'Numeric',
            'date'     => 'Dată',
        ],

        'datagrid' => [
            'target'     => 'Compară',
            'comparator' => 'Comparator',
            'weight'     => 'Pondere',
            'blocking'   => 'Cheie de blocare',
            'sort-order' => 'Ordine de afișare',
            'yes'        => 'Da',
            'no'         => 'Nu',
            'edit'       => 'Editează',
            'delete'     => 'Șterge',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Doar codul, eticheta sau un câmp care poate fi căutat pot fi cheie de blocare.',
            'too-many'                => 'Un tip de entitate poate avea cel mult :count reguli de potrivire.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalență',
        'title'          => 'Reguli de prevalență',
        'info'           => 'Stabilește ce valoare păstrează înregistrarea de referință atunci când două înregistrări de acest tip sunt îmbinate. Fără reguli, înregistrarea rămasă își păstrează valoarea, cu excepția cazului în care este goală.',
        'create-btn'     => 'Adaugă regulă de prevalență',
        'create-success' => 'Regula de prevalență a fost creată cu succes.',
        'update-success' => 'Regula de prevalență a fost actualizată cu succes.',
        'delete-success' => 'Regula de prevalență a fost ștearsă cu succes.',

        'create' => [
            'title' => 'Adaugă regulă de prevalență',
        ],

        'edit' => [
            'title' => 'Editează regula de prevalență',
        ],

        'form' => [
            'target'            => 'Se aplică la',
            'select-target'     => 'Selectează la ce se aplică regula',
            'target-info'       => 'Un câmp, eticheta înregistrării sau toate câmpurile fără regulă proprie.',
            'strategy'          => 'Strategie',
            'select-strategy'   => 'Selectează o strategie',
            'strategy-info'     => 'Cum este aleasă valoarea păstrată. Responsabilii de date pot în continuare modifica fiecare alegere pe ecranul de îmbinare.',
            'source-order'      => 'Ordinea surselor',
            'select-sources'    => 'Selectează sisteme sursă',
            'source-order-info' => 'Cel mai de încredere sistem sursă primul. Sistemele omise urmează prioritatea lor. Lasă gol pentru a folosi doar prioritatea sistemelor sursă.',
            'sort-order'        => 'Ordine de afișare',
            'save-btn'          => 'Salvează regula de prevalență',
        ],

        'targets' => [
            'default' => 'Implicit pentru toate câmpurile',
            'label'   => 'Etichetă',
        ],

        'strategies' => [
            'source_priority' => 'Cea mai de încredere sursă',
            'most_recent'     => 'Actualizat cel mai recent',
            'most_complete'   => 'Cel mai complet',
            'keep_target'     => 'Păstrează înregistrarea rămasă',
            'manual'          => 'Decide responsabilul de date',
        ],

        'datagrid' => [
            'target'     => 'Se aplică la',
            'strategy'   => 'Strategie',
            'sort-order' => 'Ordine de afișare',
            'edit'       => 'Editează',
            'delete'     => 'Șterge',
        ],

        'validation' => [
            'target-taken' => 'Acest tip de entitate are deja o regulă de prevalență pentru această țintă.',
            'too-many'     => 'Un tip de entitate poate avea cel mult :count reguli de prevalență.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validare',
        'title'          => 'Reguli de validare',
        'info'           => 'Verificați valorile câmpurilor dincolo de tipul lor. Regulile de blocare refuză o salvare, un import sau o scriere prin API; regulile de avertizare le lasă să treacă și generează un avertisment privind calitatea datelor.',
        'create-btn'     => 'Adăugați o regulă de validare',
        'create-success' => 'Regula de validare a fost creată cu succes.',
        'update-success' => 'Regula de validare a fost actualizată cu succes.',
        'delete-success' => 'Regula de validare a fost ștearsă cu succes.',

        'create' => [
            'title' => 'Adăugați o regulă de validare',
        ],

        'edit' => [
            'title' => 'Editați regula de validare',
        ],

        'form' => [
            'field'            => 'Câmp',
            'select-field'     => 'Selectați un câmp',
            'type'             => 'Regulă',
            'select-type'      => 'Selectați o regulă',
            'severity'         => 'Severitate',
            'severity-info'    => '„Blocare” refuză valorile care încalcă regula. „Avertizare” le salvează și raportează un avertisment de calitate.',
            'pattern'          => 'Model',
            'pattern-info'     => 'O expresie regulată cu delimitatori, de exemplu /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Valoare',
            'min-length'       => 'Lungime minimă',
            'max-length'       => 'Lungime maximă',
            'operator'         => 'Comparație',
            'other-field'      => 'Comparat cu',
            'other-field-info' => 'Regula este respectată cât timp celălalt câmp este gol.',
            'codes'            => 'Coduri permise',
            'codes-info'       => 'Un cod pe linie sau separate prin virgulă. Valorile sunt comparate exact.',
            'after'            => 'Nu înainte de',
            'before'           => 'Nu după',
            'date-info'        => 'O dată precum 2020-01-01 sau o dată relativă precum today sau -18 years. Lăsați unul gol pentru un interval deschis.',
            'messages'         => 'Mesaj de eroare',
            'messages-info'    => 'Afișat când o valoare încalcă regula, în limba persoanei sau a clientului API. Lăsați gol pentru mesajul implicit. :attribute reprezintă numele câmpului.',
            'sort-order'       => 'Ordine de sortare',
            'save-btn'         => 'Salvați regula de validare',
        ],

        'types' => [
            'regex'        => 'Se potrivește cu modelul',
            'min'          => 'Valoare minimă',
            'max'          => 'Valoare maximă',
            'length'       => 'Lungime',
            'cross_field'  => 'Comparat cu alt câmp',
            'one_of_codes' => 'Unul dintre codurile listate',
            'date_range'   => 'Interval de date',
        ],

        'severities' => [
            'block' => 'Blocare',
            'warn'  => 'Avertizare',
        ],

        'operators' => [
            'eq'  => 'egal cu',
            'neq' => 'diferit de',
            'gt'  => 'mai mare decât',
            'gte' => 'cel puțin',
            'lt'  => 'mai mic decât',
            'lte' => 'cel mult',
        ],

        'messages' => [
            'default'      => ':attribute nu este valid.',
            'regex'        => ':attribute nu are formatul așteptat.',
            'min'          => ':attribute trebuie să fie cel puțin :value.',
            'max'          => ':attribute trebuie să fie cel mult :value.',
            'length'       => ':attribute trebuie să aibă între :min și :max caractere.',
            'cross_field'  => ':attribute trebuie să fie :operator :field.',
            'one_of_codes' => ':attribute trebuie să fie unul dintre: :codes.',
            'date_range'   => ':attribute trebuie să fie o dată din intervalul permis.',
        ],

        'datagrid' => [
            'field'      => 'Câmp',
            'type'       => 'Regulă',
            'severity'   => 'Severitate',
            'sort-order' => 'Ordine de sortare',
            'edit'       => 'Editați',
            'delete'     => 'Ștergeți',
        ],

        'validation' => [
            'too-many'     => 'Un tip de entitate poate avea cel mult :count reguli de validare.',
            'invalid-date' => 'Introduceți o dată sau o dată relativă precum today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Standardizare',
        'info'           => 'Curățați valorile înainte de a fi validate și salvate, din administrare, din importuri și din API. Transformările unui câmp rulează în ordinea de sortare.',
        'create-btn'     => 'Adăugați o transformare',
        'create-success' => 'Transformarea a fost creată cu succes.',
        'update-success' => 'Transformarea a fost actualizată cu succes.',
        'delete-success' => 'Transformarea a fost ștearsă cu succes.',

        'create' => [
            'title' => 'Adăugați o transformare',
        ],

        'edit' => [
            'title' => 'Editați transformarea',
        ],

        'form' => [
            'field'            => 'Câmp',
            'select-field'     => 'Selectați un câmp',
            'transform'        => 'Transformare',
            'select-transform' => 'Selectați o transformare',
            'transform-info'   => 'Se aplică doar valorilor text; celelalte valori rămân neschimbate.',
            'sort-order'       => 'Ordine de sortare',
            'sort-order-info'  => 'Transformările aceluiași câmp rulează de la cea mai mică ordine de sortare în sus.',
            'save-btn'         => 'Salvați transformarea',
        ],

        'types' => [
            'trim'            => 'Elimină spațiile de la capete',
            'lower'           => 'Litere mici',
            'upper'           => 'Litere mari',
            'title'           => 'Prima literă majusculă la fiecare cuvânt',
            'collapse_spaces' => 'Reduce spațiile repetate',
            'email_normalize' => 'Normalizează adresa de e-mail',
            'phone_e164'      => 'Normalizează numărul de telefon (E.164)',
            'strip_non_alnum' => 'Păstrează doar litere și cifre',
        ],

        'datagrid' => [
            'field'      => 'Câmp',
            'transform'  => 'Transformare',
            'sort-order' => 'Ordine de sortare',
            'edit'       => 'Editați',
            'delete'     => 'Ștergeți',
        ],

        'validation' => [
            'taken'    => 'Acest câmp rulează deja această transformare.',
            'too-many' => 'Un tip de entitate poate avea cel mult :count transformări.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Avertisment de validare',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Răsfoiește :type de dedesubt',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Nivel superior',
        ],

        'datagrid' => [
            'erase'         => 'Șterge definitiv',
            'parent'        => 'Părinte',
            'top-level'     => 'Nivel superior',
            'children'      => 'Copii',
            'view-children' => 'Afișează înregistrările de sub aceasta (:count)',
        ],

        'erase' => [
            'btn'             => 'Șterge definitiv',
            'confirm-title'   => 'Ștergeți definitiv această înregistrare?',
            'confirm-message' => 'Toate valorile, eticheta și codul acestei înregistrări și ale duplicatelor îmbinate în ea sunt eliminate, împreună cu istoricul, cererile de modificare și problemele de calitate. Înregistrarea rămâne ca substituent șters, astfel încât ID-urile sursă și legăturile să funcționeze în continuare. Acțiunea nu poate fi anulată.',
            'success'         => 'Înregistrare ștearsă definitiv.',
            'not-retired'     => 'Doar o înregistrare retrasă poate fi ștearsă. Retrageți-o mai întâi.',
        ],

        'mass-retire' => [
            'success' => ':count înregistrare(ări) retrasă(e).',
            'pending' => ':count retragere(i) trimisă(e) spre aprobare.',
            'skipped' => ':count înregistrare(ări) omisă(e) deoarece nu sunt ciornă sau active, ori au fost modificate de altcineva.',
            'none'    => 'Niciuna dintre înregistrările selectate nu a putut fi retrasă.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Aprobare necesară: cerere de modificare #:id',
            'description'           => ':requester a trimis o modificare „:operation” pentru :type spre aprobarea ta.',
            'description-automated' => 'O modificare automată „:operation” pentru :type așteaptă aprobarea ta.',
        ],
    ],

    'access' => [
        'menu'           => 'Acces la date',
        'acl'            => [
            'index'  => 'Acces la date',
            'create' => 'Creează',
            'edit'   => 'Editează',
            'delete' => 'Șterge',
        ],
        'principals' => [
            'role' => 'Rol',
            'user' => 'Utilizator',
        ],
        'actions' => [
            'view'    => 'Vizualizare',
            'edit'    => 'Editare',
            'approve' => 'Aprobare',
        ],
        'index' => [
            'title'      => 'Acces la date',
            'create-btn' => 'Adaugă permisiune',
            'info'       => 'Permisiunile stabilesc la ce înregistrări ale tipurilor de entitate cu domeniu restrâns are acces fiecare rol sau utilizator: înregistrarea aleasă și tot ce se află sub ea. Accesul provenit din rolul unui utilizator și cel din permisiunile acordate utilizatorului se însumează.',
        ],
        'create' => [
            'title' => 'Adaugă permisiune de acces la date',
        ],
        'edit' => [
            'title' => 'Editează permisiunea de acces la date',
        ],
        'form' => [
            'save-btn'         => 'Salvează permisiunea',
            'principal'        => 'Acordată pentru',
            'principal-info'   => 'O permisiune de rol se aplică tuturor utilizatorilor cu acel rol. Acordă-o unui singur utilizator pentru o excepție.',
            'principal-type'   => 'Acordă pentru',
            'role'             => 'Rol',
            'user'             => 'Utilizator',
            'scope'            => 'Domeniu',
            'scope-info'       => 'Permisiunea acoperă înregistrarea aleasă și toate înregistrările de sub ea din ierarhie.',
            'root'             => 'Rădăcina subarborelui',
            'root-info'        => 'De exemplu un sit sau o fabrică. Înregistrările de sub ea moștenesc permisiunea.',
            'entity-type'      => 'Tip de entitate',
            'entity-type-info' => 'Limitează permisiunea la un tip de entitate cu domeniu restrâns sau lasă gol pentru toate tipurile restrânse.',
            'any-entity-type'  => 'Toate tipurile de entitate cu domeniu restrâns',
            'actions'          => 'Acțiuni',
            'actions-info'     => 'Vizualizare citește înregistrările, Editare modifică, retrage, îmbină și creează înregistrări sub rădăcină, Aprobare decide asupra modificărilor reținute. Editare și Aprobare includ Vizualizare.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tip',
            'principal'         => 'Acordată pentru',
            'missing-principal' => 'Șters',
            'entity-type'       => 'Tip de entitate',
            'root'              => 'Rădăcina subarborelui',
            'actions'           => 'Acțiuni',
            'edit'              => 'Editează',
            'delete'            => 'Șterge',
        ],
        'role-card' => [
            'title'  => 'Acces la datele master',
            'info'   => 'La ce înregistrări ale tipurilor de entitate cu domeniu restrâns au acces utilizatorii cu acest rol. Fiecare permisiune acoperă înregistrarea rădăcină și tot ce se află sub ea.',
            'empty'  => 'Acest rol nu are încă permisiuni de acces la datele master.',
            'remove' => 'Elimină',
            'add'    => 'Adaugă o permisiune',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restricționează accesul după subarbore',
            'is-scoped-info' => 'Când este activat, utilizatorii au acces doar la înregistrările de acest tip din subarborii acordați rolului lor sau lor înșiși în ecranul Acces la date. Lasă dezactivat pentru datele de referință partajate.',
        ],
        'validation' => [
            'record-not-found'    => 'Alege ca rădăcină a subarborelui o înregistrare pe care o poți vedea.',
            'parent-out-of-scope' => 'Alege un părinte dintr-un subarbore în care ai acces de editare.',
        ],
        'create-success' => 'Permisiunea de acces la date a fost adăugată.',
        'update-success' => 'Permisiunea de acces la date a fost actualizată.',
        'delete-success' => 'Permisiunea de acces la date a fost ștearsă.',
    ],

    'install' => [
        'migrating' => 'Se rulează migrările datelor master.',
        'demo'      => 'Se instalează datele demo pentru producție.',
        'done'      => 'Gestionarea datelor master este instalată. Module active: :modules.',
    ],
];
