<?php

return [
    'menu' => [
        'master-data' => 'Dati anagrafici',
        'icons'       => 'Icone',
    ],

    'acl' => [
        'master-data' => 'Dati anagrafici',
        'icons'       => [
            'index'  => 'Icone',
            'create' => 'Crea',
            'edit'   => 'Modifica',
            'delete' => 'Elimina',
        ],

        'records' => [
            'erase' => 'Cancella definitivamente',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dati anagrafici',
        'inside'     => 'All’interno di: :types',
        'top-level'  => 'Livello principale',
        'standalone' => 'Autonomo',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dati anagrafici',
            'records'     => 'Record',
            'upsert'      => 'Inserisci o aggiorna per ID del sistema di origine',
        ],

        'validation-failed'     => 'Convalida non riuscita.',
        'change-held'           => 'La modifica è in attesa di approvazione.',
        'xref-not-found'        => 'Nessun record corrisponde a questo ID esterno.',
        'entity-type-not-found' => 'Il tipo di entità non è stato trovato.',
        'record-not-found'      => 'Il record non è stato trovato.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipo di entità',
            'source-system'       => 'Sistema di origine',
            'code-list'           => 'Elenco codici',
            'entity-type-info'    => 'Il tipo di entità di cui il file contiene i record.',
            'source-system-info'  => 'Il sistema da cui proviene il file. I suoi ID esterni vengono letti dalla colonna external_id.',
            'code-list-info'      => 'L\'elenco in cui importare. I codici vengono scritti nella sua versione bozza, che pubblichi separatamente.',
            'mapping-info'        => 'Una riga per ogni colonna rinominata: colonna del file=codice del campo, oppure codice del campo-lingua per un campo per lingua, ad es. Asset Num=external_id e Description=name. Le colonne senza riga vengono lette con il proprio nome.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Scegli un tipo di entità attivo di cui puoi creare o modificare i record.',
            'source-system-invalid'              => 'Scegli un sistema di origine attivo.',
            'code-list-invalid'                  => 'Scegli un elenco di codici esistente che puoi modificare.',
            'external-id-without-source-system'  => 'Il file ha una colonna di ID esterni (:column). Scegli il sistema di origine da cui proviene oppure rimuovi la colonna.',
        ],

        'schedule' => [
            'job-not-found'  => 'Nessun processo di importazione ha il codice :code.',
            'not-masterdata' => 'Il processo :code non è un\'importazione di dati anagrafici.',
            'no-user'        => 'Indica l\'amministratore con cui viene eseguita l\'importazione con --user o MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Nessun amministratore attivo ha l\'e-mail :email.',
            'file-not-found' => 'Il file :path non è stato trovato sul disco :disk.',
            'nothing'        => 'Nessun file è in attesa in :path sul disco :disk.',
            'queued'         => 'File :file messo in coda per il processo :code, monitorato come #:id.',
            'imported'       => 'File :file importato per il processo :code, monitorato come #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demo di produzione pronta: :types tipi di entità, :systems sistemi di origine, :records record in un albero di siti, :xrefs riferimenti incrociati, :lists elenchi di codici, :fields campi, :aliases alias.',
        'invalid-template' => 'Impossibile leggere il modello :name oppure la versione del formato non è supportata.',
    ],

    'platform-module' => [
        'name' => 'Dati anagrafici (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipi di entità eliminati: :count.',
        'mass-delete-skipped' => 'Non eliminati perché contengono ancora record: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipi padre',
            'records-count'        => 'Record',
            'fields-count'         => 'Campi',
            'updated-at'           => 'Aggiornato',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordinamento',
        ],

        'edit' => [
            'schema-version' => 'Schema v:version',
        ],

        'form' => [
            'basics'                    => 'Informazioni di base',
            'identity'                  => 'Identità',
            'hierarchy'                 => 'Gerarchia',
            'governance'                => 'Gestione',
            'quality-matching'          => 'Qualità e corrispondenza',
            'matching-info'             => 'Le righe in arrivo dai sistemi di origine vengono abbinate ai record esistenti nella casella degli abbinamenti.',
            'auto-match-threshold'      => 'Soglia di abbinamento automatico',
            'auto-match-off'            => 'Disattivato',
            'auto-match-threshold-info' => 'Lascia vuoto per esaminare ogni riga. Da 0,5 a 1: una riga il cui miglior suggerimento raggiunge almeno questo punteggio, senza altri suggerimenti altrettanto alti, viene accettata automaticamente. I tipi che richiedono approvazione mantengono comunque il collegamento in attesa di approvazione.',
            'match-on-write'            => 'Cerca duplicati al salvataggio',
            'match-on-write-info'       => 'Ogni record salvato viene confrontato in background con gli altri record usando le regole di abbinamento. Disattiva per trovare i duplicati solo nelle analisi di qualità.',
            'manage-icons'              => 'Gestisci icone',
            'sort-order'                => 'Ordinamento',
            'sort-order-info'           => 'I numeri più bassi compaiono per primi, da 0 a 9999; i tipi con lo stesso numero sono ordinati per codice.',
        ],

        'quick-create' => [
            'code-info'             => 'Lettere minuscole, cifre e trattini bassi, iniziando con una lettera. Non può essere modificato dopo il salvataggio.',
            'code-pattern-info'     => 'Usa {seq} o {seq:6} per il numero progressivo e {TYPE} per il codice del tipo, ad esempio {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'I record arrivano da più sistemi di origine e vengono abbinati e uniti in un unico golden record.',
                'centralized'   => 'I record vengono creati e gestiti qui, poi pubblicati verso i sistemi che li utilizzano.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regola di abbinamento su :target',
        ],

        'show' => [
            'auto-matched' => 'Abbinato automaticamente',
            'auto-pending' => 'Abbinato automaticamente; il collegamento è in attesa di approvazione.',
        ],

        'datagrid' => [
            'decision' => 'Decisione',
            'auto'     => 'Abbinato automaticamente',
            'manual'   => 'Manuale',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Utilizzato da',
            'empty'        => 'Nessun job di esportazione pubblica record di questo tipo e stato.',
            'job'          => 'Job di esportazione',
            'format'       => 'Formato',
            'mode'         => 'Modalità',
            'full'         => 'Completa',
            'delta'        => 'Solo modifiche',
            'last-run-at'  => 'Ultima esecuzione completata',
            'no-run'       => 'Mai eseguito',
            'record-state' => 'Questo record',
            'states'       => [
                'never_run'  => 'Non ancora esportato',
                'pending'    => 'Modificato dall\'ultima esecuzione',
                'included'   => 'Incluso nell\'ultima esecuzione',
                'up_to_date' => 'Aggiornato',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Icona creata con successo.',
        'update-success' => 'Icona aggiornata con successo.',
        'delete-success' => 'Icona eliminata con successo.',
        'delete-in-use'  => '{1} Questa icona è mostrata da un tipo di entità e non può essere eliminata.|[2,*] Questa icona è mostrata da :count tipi di entità e non può essere eliminata.',
        'index'          => [
            'title'      => 'Icone',
            'create-btn' => 'Aggiungi icona',
        ],

        'create' => [
            'title'    => 'Aggiungi icona',
            'save-btn' => 'Salva icona',
        ],

        'edit' => [
            'title'    => 'Modifica icona',
            'save-btn' => 'Salva icona',
        ],

        'form' => [
            'code'      => 'Codice',
            'name'      => 'Nome',
            'file'      => 'File icona',
            'file-info' => 'SVG, PNG o WebP, fino a :size KB. Usa una sola forma su sfondo trasparente: prende il colore del tema.',
            'preview'   => 'Anteprima',
            'usage'     => '{0} Nessun tipo di entità mostra ancora questa icona.|{1} Mostrata da un tipo di entità.|[2,*] Mostrata da :count tipi di entità.',
        ],

        'library' => [
            'barcode'    => 'Codice a barre',
            'boxes'      => 'Materiali',
            'briefcase'  => 'Fornitore',
            'building_2' => 'Azienda',
            'cpu'        => 'Cespite',
            'factory'    => 'Stabilimento',
            'file_text'  => 'Contratto',
            'globe'      => 'Paese',
            'handshake'  => 'Partner',
            'landmark'   => 'Entità legale',
            'map_pin'    => 'Ubicazione',
            'network'    => 'Gerarchia',
            'package'    => 'Prodotto',
            'store'      => 'Negozio',
            'tag'        => 'Categoria',
            'truck'      => 'Fornitore di servizi',
            'user_round' => 'Contatto',
            'users'      => 'Cliente',
            'warehouse'  => 'Magazzino',
            'wrench'     => 'Attrezzatura',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icona',
            'code'    => 'Codice',
            'name'    => 'Nome',
            'usage'   => 'Usata da',
            'edit'    => 'Modifica',
            'delete'  => 'Elimina',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Abbinamento',
        'title'          => 'Regole di abbinamento',
        'info'           => 'Stabilisci come vengono confrontati i record di questo tipo per trovare i duplicati e abbinare le righe in entrata. Senza regole vengono confrontati il codice e l\'etichetta.',
        'create-btn'     => 'Aggiungi regola di abbinamento',
        'create-success' => 'Regola di abbinamento creata con successo.',
        'update-success' => 'Regola di abbinamento aggiornata con successo.',
        'delete-success' => 'Regola di abbinamento eliminata con successo.',

        'create' => [
            'title' => 'Aggiungi regola di abbinamento',
        ],

        'edit' => [
            'title' => 'Modifica regola di abbinamento',
        ],

        'form' => [
            'target'            => 'Confronta',
            'select-target'     => 'Seleziona cosa confrontare',
            'target-info'       => 'Il codice del record, la sua etichetta o un campo di questo tipo di entità.',
            'comparator'        => 'Comparatore',
            'select-comparator' => 'Seleziona un comparatore',
            'comparator-info'   => 'Come vengono confrontati i due valori, ad es. Approssimativo per nomi con errori di battitura o Numerico per gli importi.',
            'weight'            => 'Peso',
            'weight-info'       => 'Quanto conta questa regola, da 0,1 a 100. Il punteggio è la media ponderata delle regole per cui la riga in entrata ha un valore.',
            'tolerance'         => 'Tolleranza',
            'tolerance-info'    => 'La differenza massima tra due numeri perché siano ancora considerati simili. Lascia vuoto per abbinare solo numeri uguali.',
            'days'              => 'Intervallo di date (giorni)',
            'days-info'         => 'Il numero massimo di giorni di distanza tra due date perché siano ancora considerate simili. Lascia vuoto per abbinare solo lo stesso giorno.',
            'sort-order'        => 'Ordinamento',
            'is-blocking'       => 'Chiave di blocco',
            'is-blocking-info'  => 'Cerca anche i record che condividono esattamente questo valore, anche quando codice ed etichetta sono diversi. I campi devono essere ricercabili.',
            'save-btn'          => 'Salva regola di abbinamento',
        ],

        'targets' => [
            'code'  => 'Codice',
            'label' => 'Etichetta',
        ],

        'comparators' => [
            'exact'    => 'Esatto',
            'fuzzy'    => 'Approssimativo',
            'token'    => 'Stesse parole',
            'phonetic' => 'Suono simile',
            'numeric'  => 'Numerico',
            'date'     => 'Data',
        ],

        'datagrid' => [
            'target'     => 'Confronta',
            'comparator' => 'Comparatore',
            'weight'     => 'Peso',
            'blocking'   => 'Chiave di blocco',
            'sort-order' => 'Ordinamento',
            'yes'        => 'Sì',
            'no'         => 'No',
            'edit'       => 'Modifica',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Solo il codice, l\'etichetta o un campo ricercabile possono essere una chiave di blocco.',
            'too-many'                => 'Un tipo di entità può avere al massimo :count regole di abbinamento.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalenza',
        'title'          => 'Regole di prevalenza',
        'info'           => 'Stabilisci quale valore mantiene il record master quando due record di questo tipo vengono uniti. Senza regole, il record superstite mantiene il proprio valore a meno che non sia vuoto.',
        'create-btn'     => 'Aggiungi regola di prevalenza',
        'create-success' => 'Regola di prevalenza creata con successo.',
        'update-success' => 'Regola di prevalenza aggiornata con successo.',
        'delete-success' => 'Regola di prevalenza eliminata con successo.',

        'create' => [
            'title' => 'Aggiungi regola di prevalenza',
        ],

        'edit' => [
            'title' => 'Modifica regola di prevalenza',
        ],

        'form' => [
            'target'            => 'Si applica a',
            'select-target'     => 'Seleziona a cosa si applica la regola',
            'target-info'       => 'Un campo, l\'etichetta del record o tutti i campi senza una regola propria.',
            'strategy'          => 'Strategia',
            'select-strategy'   => 'Seleziona una strategia',
            'strategy-info'     => 'Come viene scelto il valore superstite. I responsabili dei dati possono comunque modificare ogni scelta nella schermata di unione.',
            'source-order'      => 'Ordine delle origini',
            'select-sources'    => 'Seleziona i sistemi di origine',
            'source-order-info' => 'Prima il sistema di origine più affidabile. I sistemi omessi seguono la loro priorità. Lascia vuoto per usare solo la priorità dei sistemi di origine.',
            'sort-order'        => 'Ordinamento',
            'save-btn'          => 'Salva regola di prevalenza',
        ],

        'targets' => [
            'default' => 'Predefinita per tutti i campi',
            'label'   => 'Etichetta',
        ],

        'strategies' => [
            'source_priority' => 'Origine più affidabile',
            'most_recent'     => 'Aggiornato più di recente',
            'most_complete'   => 'Più completo',
            'keep_target'     => 'Mantieni il superstite',
            'manual'          => 'Decide il responsabile dei dati',
        ],

        'datagrid' => [
            'target'     => 'Si applica a',
            'strategy'   => 'Strategia',
            'sort-order' => 'Ordinamento',
            'edit'       => 'Modifica',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'target-taken' => 'Questo tipo di entità ha già una regola di prevalenza per questa destinazione.',
            'too-many'     => 'Un tipo di entità può avere al massimo :count regole di prevalenza.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Sfoglia :type sottostanti',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Livello superiore',
        ],

        'datagrid' => [
            'erase'         => 'Cancella definitivamente',
            'parent'        => 'Padre',
            'top-level'     => 'Livello superiore',
            'children'      => 'Figli',
            'view-children' => 'Mostra i :count record sotto questo',
        ],

        'erase' => [
            'btn'             => 'Cancella definitivamente',
            'confirm-title'   => 'Cancellare definitivamente questo record?',
            'confirm-message' => 'Tutti i valori, l\'etichetta e il codice di questo record e dei duplicati uniti in esso vengono rimossi, insieme alla cronologia, alle richieste di modifica e ai problemi di qualità. Il record resta come segnaposto cancellato, così i suoi ID di origine e i collegamenti continuano a funzionare. L\'operazione non può essere annullata.',
            'success'         => 'Record cancellato definitivamente.',
            'not-retired'     => 'Solo un record ritirato può essere cancellato. Ritiralo prima.',
        ],

        'mass-retire' => [
            'success' => ':count record ritirati.',
            'pending' => ':count ritiri inviati per l\'approvazione.',
            'skipped' => ':count record saltati perché non sono bozze né attivi, oppure sono stati modificati da qualcun altro.',
            'none'    => 'Nessuno dei record selezionati è stato ritirato.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Approvazione richiesta: richiesta di modifica #:id',
            'description'           => ':requester ha inviato una modifica ":operation" su :type per la tua approvazione.',
            'description-automated' => 'Una modifica automatica ":operation" su :type è in attesa della tua approvazione.',
        ],
    ],

    'access' => [
        'menu'           => 'Accesso ai dati',
        'acl'            => [
            'index'  => 'Accesso ai dati',
            'create' => 'Crea',
            'edit'   => 'Modifica',
            'delete' => 'Elimina',
        ],
        'principals' => [
            'role' => 'Ruolo',
            'user' => 'Utente',
        ],
        'actions' => [
            'view'    => 'Visualizza',
            'edit'    => 'Modifica',
            'approve' => 'Approva',
        ],
        'index' => [
            'title'      => 'Accesso ai dati',
            'create-btn' => 'Aggiungi autorizzazione',
            'info'       => 'Le autorizzazioni stabiliscono a quali record dei tipi di entità con ambito accede ogni ruolo o utente: il record scelto e tutto ciò che si trova sotto. L\'accesso derivante dal ruolo di un utente e quello delle autorizzazioni concesse all\'utente si sommano.',
        ],
        'create' => [
            'title' => 'Aggiungi autorizzazione di accesso ai dati',
        ],
        'edit' => [
            'title' => 'Modifica autorizzazione di accesso ai dati',
        ],
        'form' => [
            'save-btn'         => 'Salva autorizzazione',
            'principal'        => 'Concessa a',
            'principal-info'   => 'Un\'autorizzazione di ruolo vale per tutti gli utenti con quel ruolo. Concedila a un singolo utente per un\'eccezione.',
            'principal-type'   => 'Concedi a',
            'role'             => 'Ruolo',
            'user'             => 'Utente',
            'scope'            => 'Ambito',
            'scope-info'       => 'L\'autorizzazione copre il record scelto e ogni record sottostante nella gerarchia.',
            'root'             => 'Radice del sottoalbero',
            'root-info'        => 'Ad esempio un sito o uno stabilimento. I record sottostanti ereditano l\'autorizzazione.',
            'entity-type'      => 'Tipo di entità',
            'entity-type-info' => 'Limita l\'autorizzazione a un tipo di entità con ambito oppure lascia vuoto per tutti i tipi con ambito.',
            'any-entity-type'  => 'Tutti i tipi di entità con ambito',
            'actions'          => 'Azioni',
            'actions-info'     => 'Visualizza legge i record, Modifica cambia, ritira, unisce e crea record sotto la radice, Approva decide le modifiche in sospeso. Modifica e Approva includono Visualizza.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipo',
            'principal'         => 'Concessa a',
            'missing-principal' => 'Eliminato',
            'entity-type'       => 'Tipo di entità',
            'root'              => 'Radice del sottoalbero',
            'actions'           => 'Azioni',
            'edit'              => 'Modifica',
            'delete'            => 'Elimina',
        ],
        'role-card' => [
            'title'  => 'Accesso ai dati anagrafici',
            'info'   => 'A quali record dei tipi di entità con ambito accedono gli utenti con questo ruolo. Ogni autorizzazione copre il proprio record radice e tutto ciò che si trova sotto.',
            'empty'  => 'Questo ruolo non ha ancora autorizzazioni di accesso ai dati anagrafici.',
            'remove' => 'Rimuovi',
            'add'    => 'Aggiungi un\'autorizzazione',
        ],
        'entity-type' => [
            'is-scoped'      => 'Limita l\'accesso per sottoalbero',
            'is-scoped-info' => 'Se attivo, gli utenti accedono solo ai record di questo tipo all\'interno dei sottoalberi concessi al loro ruolo o a loro nella schermata Accesso ai dati. Lascia disattivato per i dati di riferimento condivisi.',
        ],
        'validation' => [
            'record-not-found'    => 'Scegli come radice del sottoalbero un record che puoi vedere.',
            'parent-out-of-scope' => 'Scegli un elemento padre all\'interno di un sottoalbero in cui hai accesso in modifica.',
        ],
        'create-success' => 'Autorizzazione di accesso ai dati aggiunta.',
        'update-success' => 'Autorizzazione di accesso ai dati aggiornata.',
        'delete-success' => 'Autorizzazione di accesso ai dati eliminata.',
    ],
];
