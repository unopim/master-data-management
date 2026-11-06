<?php

return [
    'menu' => [
        'master-data' => 'Dades mestres',
        'icons'       => 'Icones',
    ],

    'acl' => [
        'master-data' => 'Dades mestres',
        'icons'       => [
            'index'  => 'Icones',
            'create' => 'Crea',
            'edit'   => 'Edita',
            'delete' => 'Elimina',
        ],

        'records' => [
            'erase' => 'Esborra definitivament',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dades mestres',
        'inside'     => 'Dins de: :types',
        'top-level'  => 'Nivell superior',
        'standalone' => 'Independent',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dades mestres',
            'records'     => 'Registres',
            'upsert'      => 'Insereix o actualitza per ID del sistema d\'origen',
        ],

        'validation-failed'     => 'La validació ha fallat.',
        'change-held'           => 'El canvi està pendent d\'aprovació.',
        'xref-not-found'        => 'Cap registre coincideix amb aquest identificador extern.',
        'entity-type-not-found' => 'No s\'ha trobat el tipus d\'entitat.',
        'record-not-found'      => 'No s\'ha trobat el registre.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipus d\'entitat',
            'source-system'       => 'Sistema d\'origen',
            'code-list'           => 'Llista de codis',
            'entity-type-info'    => 'El tipus d\'entitat els registres del qual conté el fitxer.',
            'source-system-info'  => 'El sistema d\'on prové el fitxer. Els seus identificadors externs es llegeixen de la columna external_id.',
            'code-list-info'      => 'La llista on s\'importa. Els codis s\'escriuen a la seva versió esborrany, que publiqueu per separat.',
            'mapping-info'        => 'Una línia per columna reanomenada: columna del fitxer=codi del camp, o codi del camp-idioma per a un camp per idioma, p. ex. Asset Num=external_id i Description=name. Les columnes sense línia es llegeixen pel seu propi nom.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Trieu un tipus d\'entitat actiu els registres del qual pugueu crear o editar.',
            'source-system-invalid'              => 'Trieu un sistema d\'origen actiu.',
            'code-list-invalid'                  => 'Trieu una llista de codis existent que pugueu editar.',
            'external-id-without-source-system'  => 'El fitxer té una columna d\'identificador extern (:column). Trieu el sistema d\'origen d\'on prové o elimineu la columna.',
        ],

        'schedule' => [
            'job-not-found'  => 'Cap tasca d\'importació té el codi :code.',
            'not-masterdata' => 'La tasca :code no és una importació de dades mestres.',
            'no-user'        => 'Indiqueu l\'administrador amb què s\'executa la importació amb --user o MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Cap administrador actiu té el correu electrònic :email.',
            'file-not-found' => 'No s\'ha trobat el fitxer :path al disc :disk.',
            'nothing'        => 'No hi ha cap fitxer pendent a :path al disc :disk.',
            'queued'         => 'S\'ha posat en cua :file per a la tasca :code, amb seguiment com a #:id.',
            'imported'       => 'S\'ha importat :file per a la tasca :code, amb seguiment com a #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demostració de fabricació preparada: :types tipus d\'entitat, :systems sistemes d\'origen, :records registres en un arbre de centres, :xrefs referències creuades, :lists llistes de codis, :fields camps, :aliases àlies.',
        'invalid-template' => 'No s\'ha pogut llegir la plantilla :name o té una versió de format no compatible.',
    ],

    'platform-module' => [
        'name' => 'Dades mestres (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipus d\'entitat eliminats: :count.',
        'mass-delete-skipped' => 'No s\'han eliminat perquè encara tenen registres: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipus pare',
            'records-count'        => 'Registres',
            'fields-count'         => 'Camps',
            'updated-at'           => 'Actualitzat',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordre',
        ],

        'edit' => [
            'schema-version' => 'Esquema v:version',
        ],

        'form' => [
            'basics'                    => 'Bàsics',
            'identity'                  => 'Identitat',
            'hierarchy'                 => 'Jerarquia',
            'governance'                => 'Governança',
            'quality-matching'          => 'Qualitat i coincidència',
            'matching-info'             => 'Les files entrants dels sistemes d\'origen es fan coincidir amb registres existents a la safata de coincidències.',
            'auto-match-threshold'      => 'Llindar de coincidència automàtica',
            'auto-match-off'            => 'Desactivat',
            'auto-match-threshold-info' => 'Deixeu-ho buit per revisar cada fila. De 0,5 a 1: una fila el millor suggeriment de la qual arriba com a mínim a aquest valor, sense cap altre suggeriment tan alt, s\'accepta automàticament. Els tipus que requereixen aprovació continuen retenint l\'enllaç fins que s\'aprovi.',
            'match-on-write'            => 'Cerca duplicats en desar',
            'match-on-write-info'       => 'Cada registre desat es compara en segon pla amb la resta de registres mitjançant les regles de coincidència. Desactiveu-ho per trobar duplicats només en les anàlisis de qualitat.',
            'manage-icons'              => 'Gestiona les icones',
            'sort-order'                => 'Ordre',
            'sort-order-info'           => 'Els números més baixos surten primer, de 0 a 9999; els tipus amb el mateix número s’ordenen pel codi.',
        ],

        'quick-create' => [
            'code-info'             => 'Lletres minúscules, dígits i guions baixos, començant per una lletra. No es pot canviar després de desar.',
            'code-pattern-info'     => 'Utilitzeu {seq} o {seq:6} per al número correlatiu i {TYPE} per al codi del tipus, per exemple {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Els registres arriben de diversos sistemes d\'origen i es comparen i fusionen en un únic registre mestre.',
                'centralized'   => 'Els registres es creen i es mantenen aquí i després es publiquen als sistemes que els utilitzen.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regla de coincidència sobre :target',
        ],

        'show' => [
            'auto-matched' => 'Coincidència automàtica',
            'auto-pending' => 'Coincidència automàtica; l\'enllaç espera aprovació.',
        ],

        'datagrid' => [
            'decision' => 'Decisió',
            'auto'     => 'Coincidència automàtica',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Consumit per',
            'empty'        => 'Cap tasca d\'exportació publica registres d\'aquest tipus i estat.',
            'job'          => 'Tasca d\'exportació',
            'format'       => 'Format',
            'mode'         => 'Mode',
            'full'         => 'Complet',
            'delta'        => 'Només canvis',
            'last-run-at'  => 'Última execució completada',
            'no-run'       => 'Mai executada',
            'record-state' => 'Aquest registre',
            'states'       => [
                'never_run'  => 'Encara no exportat',
                'pending'    => 'Canviat des de l\'última execució',
                'included'   => 'Inclòs a l\'última execució',
                'up_to_date' => 'Al dia',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Icona creada correctament.',
        'update-success' => 'Icona actualitzada correctament.',
        'delete-success' => 'Icona eliminada correctament.',
        'delete-in-use'  => '{1} Aquesta icona la mostra un tipus d\'entitat i no es pot eliminar.|[2,*] Aquesta icona la mostren :count tipus d\'entitat i no es pot eliminar.',
        'index'          => [
            'title'      => 'Icones',
            'create-btn' => 'Afegeix una icona',
        ],

        'create' => [
            'title'    => 'Afegeix una icona',
            'save-btn' => 'Desa la icona',
        ],

        'edit' => [
            'title'    => 'Edita la icona',
            'save-btn' => 'Desa la icona',
        ],

        'form' => [
            'code'      => 'Codi',
            'name'      => 'Nom',
            'file'      => 'Fitxer d\'icona',
            'file-info' => 'SVG, PNG o WebP, fins a :size KB. Utilitza una sola forma sobre un fons transparent: pren el color del tema.',
            'preview'   => 'Previsualització',
            'usage'     => '{0} Cap tipus d\'entitat mostra encara aquesta icona.|{1} Mostrada per un tipus d\'entitat.|[2,*] Mostrada per :count tipus d\'entitat.',
        ],

        'library' => [
            'barcode'    => 'Codi de barres',
            'boxes'      => 'Materials',
            'briefcase'  => 'Proveïdor',
            'building_2' => 'Empresa',
            'cpu'        => 'Actiu',
            'factory'    => 'Planta',
            'file_text'  => 'Contracte',
            'globe'      => 'País',
            'handshake'  => 'Soci',
            'landmark'   => 'Entitat legal',
            'map_pin'    => 'Ubicació',
            'network'    => 'Jerarquia',
            'package'    => 'Producte',
            'store'      => 'Botiga',
            'tag'        => 'Categoria',
            'truck'      => 'Subministrador',
            'user_round' => 'Contacte',
            'users'      => 'Client',
            'warehouse'  => 'Magatzem',
            'wrench'     => 'Equipament',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icona',
            'code'    => 'Codi',
            'name'    => 'Nom',
            'usage'   => 'Utilitzada per',
            'edit'    => 'Edita',
            'delete'  => 'Elimina',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Coincidència',
        'title'          => 'Regles de coincidència',
        'info'           => 'Decidiu com es comparen els registres d\'aquest tipus per trobar duplicats i fer coincidir les files entrants. Sense regles, es comparen el codi i l\'etiqueta.',
        'create-btn'     => 'Afegeix una regla de coincidència',
        'create-success' => 'Regla de coincidència creada correctament.',
        'update-success' => 'Regla de coincidència actualitzada correctament.',
        'delete-success' => 'Regla de coincidència eliminada correctament.',

        'create' => [
            'title' => 'Afegeix una regla de coincidència',
        ],

        'edit' => [
            'title' => 'Edita la regla de coincidència',
        ],

        'form' => [
            'target'            => 'Compara',
            'select-target'     => 'Seleccioneu què cal comparar',
            'target-info'       => 'El codi del registre, la seva etiqueta o un camp d\'aquest tipus d\'entitat.',
            'comparator'        => 'Comparador',
            'select-comparator' => 'Seleccioneu un comparador',
            'comparator-info'   => 'Com es comparen els dos valors, p. ex. Aproximat per a noms amb errades o Numèric per a imports.',
            'weight'            => 'Pes',
            'weight-info'       => 'Quant compta aquesta regla, de 0,1 a 100. La puntuació és la mitjana ponderada de les regles per a les quals la fila entrant té un valor.',
            'tolerance'         => 'Tolerància',
            'tolerance-info'    => 'La diferència més gran entre dos nombres que encara es considera similar. Deixeu-ho buit per fer coincidir només nombres iguals.',
            'days'              => 'Marge de dates (dies)',
            'days-info'         => 'El màxim de dies que poden separar dues dates perquè encara es considerin similars. Deixeu-ho buit per fer coincidir només el mateix dia.',
            'sort-order'        => 'Ordre',
            'is-blocking'       => 'Clau de bloqueig',
            'is-blocking-info'  => 'Cerca també els registres que comparteixen exactament aquest valor, encara que el codi i l\'etiqueta siguin diferents. Els camps han de ser cercables.',
            'save-btn'          => 'Desa la regla de coincidència',
        ],

        'targets' => [
            'code'  => 'Codi',
            'label' => 'Etiqueta',
        ],

        'comparators' => [
            'exact'    => 'Exacte',
            'fuzzy'    => 'Aproximat',
            'token'    => 'Mateixes paraules',
            'phonetic' => 'Sona igual',
            'numeric'  => 'Numèric',
            'date'     => 'Data',
        ],

        'datagrid' => [
            'target'     => 'Compara',
            'comparator' => 'Comparador',
            'weight'     => 'Pes',
            'blocking'   => 'Clau de bloqueig',
            'sort-order' => 'Ordre',
            'yes'        => 'Sí',
            'no'         => 'No',
            'edit'       => 'Edita',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Només el codi, l\'etiqueta o un camp cercable poden ser clau de bloqueig.',
            'too-many'                => 'Un tipus d\'entitat pot tenir com a màxim :count regles de coincidència.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalença',
        'title'          => 'Regles de prevalença',
        'info'           => 'Decideix quin valor conserva el registre mestre quan es fusionen dos registres d\'aquest tipus. Sense regles, el registre supervivent conserva el seu valor tret que sigui buit.',
        'create-btn'     => 'Afegeix una regla de prevalença',
        'create-success' => 'Regla de prevalença creada correctament.',
        'update-success' => 'Regla de prevalença actualitzada correctament.',
        'delete-success' => 'Regla de prevalença eliminada correctament.',

        'create' => [
            'title' => 'Afegeix una regla de prevalença',
        ],

        'edit' => [
            'title' => 'Edita la regla de prevalença',
        ],

        'form' => [
            'target'            => 'S\'aplica a',
            'select-target'     => 'Selecciona a què s\'aplica la regla',
            'target-info'       => 'Un camp, l\'etiqueta del registre o tots els camps sense regla pròpia.',
            'strategy'          => 'Estratègia',
            'select-strategy'   => 'Selecciona una estratègia',
            'strategy-info'     => 'Com es tria el valor supervivent. Els responsables de dades poden canviar cada elecció a la pantalla de fusió.',
            'source-order'      => 'Ordre d\'origen',
            'select-sources'    => 'Selecciona sistemes d\'origen',
            'source-order-info' => 'El sistema d\'origen més fiable primer. Els sistemes omesos segueixen la seva prioritat. Deixa-ho buit per usar només la prioritat dels sistemes d\'origen.',
            'sort-order'        => 'Ordre',
            'save-btn'          => 'Desa la regla de prevalença',
        ],

        'targets' => [
            'default' => 'Per defecte per a tots els camps',
            'label'   => 'Etiqueta',
        ],

        'strategies' => [
            'source_priority' => 'Origen més fiable',
            'most_recent'     => 'Actualitzat més recentment',
            'most_complete'   => 'Més complet',
            'keep_target'     => 'Conserva el supervivent',
            'manual'          => 'Decideix el responsable de dades',
        ],

        'datagrid' => [
            'target'     => 'S\'aplica a',
            'strategy'   => 'Estratègia',
            'sort-order' => 'Ordre',
            'edit'       => 'Edita',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'target-taken' => 'Aquest tipus d\'entitat ja té una regla de prevalença per a aquest destí.',
            'too-many'     => 'Un tipus d\'entitat pot tenir com a màxim :count regles de prevalença.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validació',
        'title'          => 'Regles de validació',
        'info'           => 'Comproveu els valors dels camps més enllà del seu tipus. Les regles de bloqueig rebutgen un desament, una importació o una escriptura per API; les regles d\'avís ho deixen passar i generen un avís de qualitat de dades.',
        'create-btn'     => 'Afegeix una regla de validació',
        'create-success' => 'Regla de validació creada correctament.',
        'update-success' => 'Regla de validació actualitzada correctament.',
        'delete-success' => 'Regla de validació eliminada correctament.',

        'create' => [
            'title' => 'Afegeix una regla de validació',
        ],

        'edit' => [
            'title' => 'Edita la regla de validació',
        ],

        'form' => [
            'field'            => 'Camp',
            'select-field'     => 'Seleccioneu un camp',
            'type'             => 'Regla',
            'select-type'      => 'Seleccioneu una regla',
            'severity'         => 'Gravetat',
            'severity-info'    => '«Bloqueja» rebutja els valors que incompleixen la regla. «Avisa» els desa i informa d\'un avís de qualitat.',
            'pattern'          => 'Patró',
            'pattern-info'     => 'Una expressió regular amb delimitadors, com ara /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Valor',
            'min-length'       => 'Longitud mínima',
            'max-length'       => 'Longitud màxima',
            'operator'         => 'Comparació',
            'other-field'      => 'Comparat amb',
            'other-field-info' => 'La regla es compleix mentre l\'altre camp estigui buit.',
            'codes'            => 'Codis permesos',
            'codes-info'       => 'Un codi per línia o separats per comes. Els valors es comparen exactament.',
            'after'            => 'No abans de',
            'before'           => 'No després de',
            'date-info'        => 'Una data com 2020-01-01 o una data relativa com today o -18 years. Deixeu-ne un en blanc per a un interval obert.',
            'messages'         => 'Missatge d\'error',
            'messages-info'    => 'Es mostra quan un valor incompleix la regla, en l\'idioma de la persona o del client API. Deixeu-ho en blanc per al missatge per defecte. :attribute representa el nom del camp.',
            'sort-order'       => 'Ordre',
            'save-btn'         => 'Desa la regla de validació',
        ],

        'types' => [
            'regex'        => 'Coincideix amb el patró',
            'min'          => 'Valor mínim',
            'max'          => 'Valor màxim',
            'length'       => 'Longitud',
            'cross_field'  => 'Comparat amb un altre camp',
            'one_of_codes' => 'Un dels codis indicats',
            'date_range'   => 'Interval de dates',
        ],

        'severities' => [
            'block' => 'Bloqueja',
            'warn'  => 'Avisa',
        ],

        'operators' => [
            'eq'  => 'igual a',
            'neq' => 'diferent de',
            'gt'  => 'més gran que',
            'gte' => 'com a mínim',
            'lt'  => 'més petit que',
            'lte' => 'com a màxim',
        ],

        'messages' => [
            'default'      => ':attribute no és vàlid.',
            'regex'        => ':attribute no té el format esperat.',
            'min'          => ':attribute ha de ser com a mínim :value.',
            'max'          => ':attribute ha de ser com a màxim :value.',
            'length'       => ':attribute ha de tenir entre :min i :max caràcters.',
            'cross_field'  => ':attribute ha de ser :operator :field.',
            'one_of_codes' => ':attribute ha de ser un dels següents: :codes.',
            'date_range'   => ':attribute ha de ser una data dins de l\'interval permès.',
            'unavailable'  => 'La comprovació :rule de :attribute ja no està disponible. Demaneu a un administrador que elimini o substitueixi la regla.',
        ],

        'datagrid' => [
            'field'      => 'Camp',
            'type'       => 'Regla',
            'severity'   => 'Gravetat',
            'sort-order' => 'Ordre',
            'edit'       => 'Edita',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'too-many'     => 'Un tipus d\'entitat pot tenir com a màxim :count regles de validació.',
            'invalid-date' => 'Introduïu una data o una data relativa com today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Estandardització',
        'info'           => 'Netegeu els valors abans que es validin i es desin, des de l\'administració, les importacions i l\'API. Les transformacions d\'un camp s\'executen segons el seu ordre.',
        'create-btn'     => 'Afegeix una transformació',
        'create-success' => 'Transformació creada correctament.',
        'update-success' => 'Transformació actualitzada correctament.',
        'delete-success' => 'Transformació eliminada correctament.',
        'unavailable'    => 'La transformació :transform de :attribute ja no està disponible. Demaneu a un administrador que l\'elimini o la substitueixi.',

        'create' => [
            'title' => 'Afegeix una transformació',
        ],

        'edit' => [
            'title' => 'Edita la transformació',
        ],

        'form' => [
            'field'            => 'Camp',
            'select-field'     => 'Seleccioneu un camp',
            'transform'        => 'Transformació',
            'select-transform' => 'Seleccioneu una transformació',
            'transform-info'   => 'S\'aplica només als valors de text; els altres valors es deixen tal com estan.',
            'sort-order'       => 'Ordre',
            'sort-order-info'  => 'Les transformacions del mateix camp s\'executen de l\'ordre més baix al més alt.',
            'save-btn'         => 'Desa la transformació',
        ],

        'types' => [
            'trim'            => 'Elimina els espais als extrems',
            'lower'           => 'Minúscules',
            'upper'           => 'Majúscules',
            'title'           => 'Majúscula inicial a cada paraula',
            'collapse_spaces' => 'Redueix els espais repetits',
            'email_normalize' => 'Normalitza l\'adreça electrònica',
            'phone_e164'      => 'Normalitza el número de telèfon (E.164)',
            'strip_non_alnum' => 'Conserva només lletres i dígits',
        ],

        'datagrid' => [
            'field'      => 'Camp',
            'transform'  => 'Transformació',
            'sort-order' => 'Ordre',
            'edit'       => 'Edita',
            'delete'     => 'Elimina',
        ],

        'validation' => [
            'taken'    => 'Aquest camp ja executa aquesta transformació.',
            'too-many' => 'Un tipus d\'entitat pot tenir com a màxim :count transformacions.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Avís de validació',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Explora els :type inferiors',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Nivell superior',
        ],

        'datagrid' => [
            'erase'         => 'Esborra definitivament',
            'parent'        => 'Pare',
            'top-level'     => 'Nivell superior',
            'children'      => 'Fills',
            'view-children' => 'Mostra els :count registres que pengen d\'aquest',
        ],

        'erase' => [
            'btn'             => 'Esborra definitivament',
            'confirm-title'   => 'Vols esborrar aquest registre definitivament?',
            'confirm-message' => 'S\'eliminaran tots els valors, l\'etiqueta i el codi d\'aquest registre i dels duplicats fusionats, juntament amb el seu historial, les sol·licituds de canvi i les incidències de qualitat. El registre es manté com a marcador esborrat perquè els identificadors d\'origen i els enllaços continuïn resolent-se. Aquesta acció no es pot desfer.',
            'success'         => 'Registre esborrat definitivament.',
            'not-retired'     => 'Només es pot esborrar un registre retirat. Retira\'l primer.',
        ],

        'mass-retire' => [
            'success' => ':count registre(s) retirat(s).',
            'pending' => ':count retirada(es) enviada(es) per a aprovació.',
            'skipped' => ':count registre(s) omès(os) perquè no són esborranys ni actius, o algú altre els ha modificat.',
            'none'    => 'No s\'ha pogut retirar cap dels registres seleccionats.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Cal aprovació: sol·licitud de canvi #:id',
            'description'           => ':requester ha enviat un canvi ":operation" a :type perquè l\'aprovis.',
            'description-automated' => 'Un canvi automàtic ":operation" a :type espera la teva aprovació.',
        ],
    ],

    'access' => [
        'menu'           => 'Accés a les dades',
        'acl'            => [
            'index'  => 'Accés a les dades',
            'create' => 'Crea',
            'edit'   => 'Edita',
            'delete' => 'Elimina',
        ],
        'principals' => [
            'role' => 'Rol',
            'user' => 'Usuari',
        ],
        'actions' => [
            'view'    => 'Visualitza',
            'edit'    => 'Edita',
            'approve' => 'Aprova',
        ],
        'index' => [
            'title'      => 'Accés a les dades',
            'create-btn' => 'Afegeix un permís',
            'info'       => 'Els permisos decideixen a quins registres dels tipus d\'entitat restringits accedeix cada rol o usuari: el registre triat i tot el que hi ha per sota. L\'accés del rol d\'un usuari i els permisos concedits a l\'usuari se sumen.',
        ],
        'create' => [
            'title' => 'Afegeix un permís d\'accés a les dades',
        ],
        'edit' => [
            'title' => 'Edita el permís d\'accés a les dades',
        ],
        'form' => [
            'save-btn'         => 'Desa el permís',
            'principal'        => 'Concedit a',
            'principal-info'   => 'Un permís de rol arriba a tots els usuaris amb aquest rol. Concedeix-lo a un sol usuari per a una excepció.',
            'principal-type'   => 'Concedeix a',
            'role'             => 'Rol',
            'user'             => 'Usuari',
            'scope'            => 'Àmbit',
            'scope-info'       => 'El permís cobreix el registre triat i tots els registres que hi ha per sota a la jerarquia.',
            'root'             => 'Arrel del subarbre',
            'root-info'        => 'Per exemple, un centre o una planta. Els registres que hi ha per sota hereten el permís.',
            'entity-type'      => 'Tipus d\'entitat',
            'entity-type-info' => 'Limita el permís a un tipus d\'entitat restringit, o deixa-ho buit per a tots els tipus restringits.',
            'any-entity-type'  => 'Tots els tipus d\'entitat restringits',
            'actions'          => 'Accions',
            'actions-info'     => 'Visualitza llegeix registres; Edita canvia, retira, fusiona i crea registres per sota de l\'arrel; Aprova decideix els canvis retinguts. Edita i Aprova inclouen Visualitza.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipus',
            'principal'         => 'Concedit a',
            'missing-principal' => 'Eliminat',
            'entity-type'       => 'Tipus d\'entitat',
            'root'              => 'Arrel del subarbre',
            'actions'           => 'Accions',
            'edit'              => 'Edita',
            'delete'            => 'Elimina',
        ],
        'role-card' => [
            'title'  => 'Accés a les dades mestres',
            'info'   => 'A quins registres dels tipus d\'entitat restringits accedeixen els usuaris amb aquest rol. Cada permís cobreix el seu registre arrel i tot el que hi ha per sota.',
            'empty'  => 'Aquest rol encara no té cap permís d\'accés a les dades mestres.',
            'remove' => 'Elimina',
            'add'    => 'Afegeix un permís',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restringeix l\'accés per subarbre',
            'is-scoped-info' => 'Quan està activat, els usuaris només accedeixen als registres d\'aquest tipus dins dels subarbres concedits al seu rol o a ells a la pantalla d\'accés a les dades. Deixa-ho desactivat per a dades de referència compartides.',
        ],
        'validation' => [
            'record-not-found'    => 'Tria un registre que puguis veure com a arrel del subarbre.',
            'parent-out-of-scope' => 'Tria un pare dins d\'un subarbre on tinguis accés d\'edició.',
        ],
        'create-success' => 'S\'ha afegit el permís d\'accés a les dades.',
        'update-success' => 'S\'ha actualitzat el permís d\'accés a les dades.',
        'delete-success' => 'S\'ha eliminat el permís d\'accés a les dades.',
    ],

    'install' => [
        'migrating' => 'S\'estan executant les migracions de dades mestres.',
        'demo'      => 'S\'estan instal·lant les dades de demostració de fabricació.',
        'done'      => 'La gestió de dades mestres està instal·lada. Mòduls actius: :modules.',
    ],
];
