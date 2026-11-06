<?php

return [
    'menu' => [
        'master-data' => 'Stammdaten',
        'icons'       => 'Symbole',
    ],

    'acl' => [
        'master-data' => 'Stammdaten',
        'icons'       => [
            'index'  => 'Symbole',
            'create' => 'Erstellen',
            'edit'   => 'Bearbeiten',
            'delete' => 'Löschen',
        ],

        'records' => [
            'erase' => 'Endgültig löschen',
        ],
    ],

    'dashboard' => [
        'title'      => 'Stammdaten',
        'inside'     => 'Innerhalb von: :types',
        'top-level'  => 'Oberste Ebene',
        'standalone' => 'Eigenständig',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Stammdaten',
            'records'     => 'Datensätze',
            'upsert'      => 'Einfügen oder Aktualisieren nach Quellsystem-ID',
        ],

        'validation-failed'     => 'Validierung fehlgeschlagen.',
        'change-held'           => 'Die Änderung wartet auf Genehmigung.',
        'xref-not-found'        => 'Kein Datensatz passt zu dieser externen ID.',
        'entity-type-not-found' => 'Der Entitätstyp wurde nicht gefunden.',
        'record-not-found'      => 'Der Datensatz wurde nicht gefunden.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Entitätstyp',
            'source-system'       => 'Quellsystem',
            'code-list'           => 'Codeliste',
            'entity-type-info'    => 'Der Entitätstyp, dessen Datensätze die Datei enthält.',
            'source-system-info'  => 'Das System, aus dem die Datei stammt. Seine externen IDs werden aus der Spalte external_id gelesen.',
            'code-list-info'      => 'Die Liste, in die importiert wird. Die Codes werden in ihre Entwurfsversion geschrieben, die Sie separat veröffentlichen.',
            'mapping-info'        => 'Eine Zeile pro umbenannter Spalte: Dateispalte=Feldcode oder Feldcode-Gebietsschema für ein Feld pro Gebietsschema, z. B. Asset Num=external_id und Description=name. Spalten ohne Zeile werden unter ihrem eigenen Namen gelesen.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Wählen Sie einen aktiven Entitätstyp, dessen Datensätze Sie anlegen oder bearbeiten dürfen.',
            'source-system-invalid'              => 'Wählen Sie ein aktives Quellsystem.',
            'code-list-invalid'                  => 'Wählen Sie eine vorhandene Codeliste, die Sie bearbeiten dürfen.',
            'external-id-without-source-system'  => 'Die Datei enthält eine Spalte mit externen IDs (:column). Wählen Sie das Quellsystem, aus dem sie stammt, oder entfernen Sie die Spalte.',
        ],

        'schedule' => [
            'job-not-found'  => 'Kein Importjob hat den Code :code.',
            'not-masterdata' => 'Der Job :code ist kein Stammdatenimport.',
            'no-user'        => 'Geben Sie mit --user oder MASTERDATA_IMPORT_USER den Admin an, unter dem der Import läuft.',
            'user-not-found' => 'Kein aktiver Admin hat die E-Mail-Adresse :email.',
            'file-not-found' => 'Die Datei :path wurde auf dem Datenträger :disk nicht gefunden.',
            'nothing'        => 'Auf dem Datenträger :disk wartet in :path keine Datei.',
            'queued'         => 'Datei :file für den Job :code in die Warteschlange gestellt, verfolgt als #:id.',
            'imported'       => 'Datei :file für den Job :code importiert, verfolgt als #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Fertigungs-Demo bereit: :types Entitätstypen, :systems Quellsysteme, :records Datensätze in einem Standortbaum, :xrefs Querverweise, :lists Codelisten, :fields Felder, :aliases Aliasse.',
        'invalid-template' => 'Die Vorlage :name konnte nicht gelesen werden oder hat eine nicht unterstützte Formatversion.',
    ],

    'platform-module' => [
        'name' => 'Stammdaten (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Gelöschte Entitätstypen: :count.',
        'mass-delete-skipped' => 'Nicht gelöscht, da noch Datensätze vorhanden sind: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Übergeordnete Typen',
            'records-count'        => 'Datensätze',
            'fields-count'         => 'Felder',
            'updated-at'           => 'Aktualisiert',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Sortierreihenfolge',
        ],

        'edit' => [
            'schema-version' => 'Schema v:version',
        ],

        'form' => [
            'basics'                    => 'Grundlagen',
            'identity'                  => 'Identität',
            'hierarchy'                 => 'Hierarchie',
            'governance'                => 'Steuerung',
            'quality-matching'          => 'Qualität und Abgleich',
            'matching-info'             => 'Eingehende Zeilen aus Quellsystemen werden im Abgleich-Eingang vorhandenen Datensätzen zugeordnet.',
            'auto-match-threshold'      => 'Schwellenwert für automatischen Abgleich',
            'auto-match-off'            => 'Aus',
            'auto-match-threshold-info' => 'Leer lassen, um jede Zeile zu prüfen. Von 0,5 bis 1: Eine Zeile, deren bester Vorschlag mindestens diesen Wert erreicht, ohne dass ein anderer Vorschlag ebenso hoch liegt, wird automatisch übernommen. Bei genehmigungspflichtigen Typen wartet die Verknüpfung weiterhin auf Genehmigung.',
            'match-on-write'            => 'Beim Speichern auf Dubletten prüfen',
            'match-on-write-info'       => 'Jeder gespeicherte Datensatz wird im Hintergrund anhand der Abgleichregeln mit den übrigen Datensätzen verglichen. Deaktivieren, um Dubletten nur bei Qualitätsprüfungen zu finden.',
            'manage-icons'              => 'Symbole verwalten',
            'sort-order'                => 'Sortierreihenfolge',
            'sort-order-info'           => 'Kleinere Zahlen werden zuerst angezeigt, von 0 bis 9999; Typen mit derselben Zahl werden nach Code sortiert.',
        ],

        'quick-create' => [
            'code-info'             => 'Kleinbuchstaben, Ziffern und Unterstriche, beginnend mit einem Buchstaben. Nach dem Speichern nicht mehr änderbar.',
            'code-pattern-info'     => 'Verwenden Sie {seq} oder {seq:6} für die laufende Nummer und {TYPE} für den Typcode, z. B. {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Datensätze kommen aus mehreren Quellsystemen und werden abgeglichen und zu einem Golden Record zusammengeführt.',
                'centralized'   => 'Datensätze werden hier angelegt und gepflegt und anschließend an die nutzenden Systeme veröffentlicht.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Abgleichregel für :target',
        ],

        'show' => [
            'auto-matched' => 'Automatisch zugeordnet',
            'auto-pending' => 'Automatisch zugeordnet; die Verknüpfung wartet auf Genehmigung.',
        ],

        'datagrid' => [
            'decision' => 'Entscheidung',
            'auto'     => 'Automatisch zugeordnet',
            'manual'   => 'Manuell',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Verwendet von',
            'empty'        => 'Kein Exportjob veröffentlicht Datensätze dieses Typs und Status.',
            'job'          => 'Exportjob',
            'format'       => 'Format',
            'mode'         => 'Modus',
            'full'         => 'Vollständig',
            'delta'        => 'Nur Änderungen',
            'last-run-at'  => 'Letzter abgeschlossener Lauf',
            'no-run'       => 'Nie ausgeführt',
            'record-state' => 'Dieser Datensatz',
            'states'       => [
                'never_run'  => 'Noch nicht exportiert',
                'pending'    => 'Seit dem letzten Lauf geändert',
                'included'   => 'Im letzten Lauf enthalten',
                'up_to_date' => 'Aktuell',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Symbol erfolgreich erstellt.',
        'update-success' => 'Symbol erfolgreich aktualisiert.',
        'delete-success' => 'Symbol erfolgreich gelöscht.',
        'delete-in-use'  => '{1} Dieses Symbol wird von einem Entitätstyp verwendet und kann nicht gelöscht werden.|[2,*] Dieses Symbol wird von :count Entitätstypen verwendet und kann nicht gelöscht werden.',
        'index'          => [
            'title'      => 'Symbole',
            'create-btn' => 'Symbol hinzufügen',
        ],

        'create' => [
            'title'    => 'Symbol hinzufügen',
            'save-btn' => 'Symbol speichern',
        ],

        'edit' => [
            'title'    => 'Symbol bearbeiten',
            'save-btn' => 'Symbol speichern',
        ],

        'form' => [
            'code'      => 'Code',
            'name'      => 'Name',
            'file'      => 'Symboldatei',
            'file-info' => 'SVG, PNG oder WebP, bis :size KB. Verwenden Sie eine einzelne Form auf transparentem Hintergrund: Sie übernimmt die Designfarbe.',
            'preview'   => 'Vorschau',
            'usage'     => '{0} Noch kein Entitätstyp verwendet dieses Symbol.|{1} Von einem Entitätstyp verwendet.|[2,*] Von :count Entitätstypen verwendet.',
        ],

        'library' => [
            'barcode'    => 'Barcode',
            'boxes'      => 'Materialien',
            'briefcase'  => 'Lieferant',
            'building_2' => 'Unternehmen',
            'cpu'        => 'Anlagegut',
            'factory'    => 'Werk',
            'file_text'  => 'Vertrag',
            'globe'      => 'Land',
            'handshake'  => 'Partner',
            'landmark'   => 'Rechtseinheit',
            'map_pin'    => 'Standort',
            'network'    => 'Hierarchie',
            'package'    => 'Produkt',
            'store'      => 'Geschäft',
            'tag'        => 'Kategorie',
            'truck'      => 'Spediteur',
            'user_round' => 'Kontakt',
            'users'      => 'Kunde',
            'warehouse'  => 'Lager',
            'wrench'     => 'Ausrüstung',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Symbol',
            'code'    => 'Code',
            'name'    => 'Name',
            'usage'   => 'Verwendet von',
            'edit'    => 'Bearbeiten',
            'delete'  => 'Löschen',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Abgleich',
        'title'          => 'Abgleichregeln',
        'info'           => 'Legen Sie fest, wie Datensätze dieses Typs verglichen werden, um Dubletten zu finden und eingehende Zeilen zuzuordnen. Ohne Regeln werden Code und Bezeichnung verglichen.',
        'create-btn'     => 'Abgleichregel hinzufügen',
        'create-success' => 'Abgleichregel erfolgreich erstellt.',
        'update-success' => 'Abgleichregel erfolgreich aktualisiert.',
        'delete-success' => 'Abgleichregel erfolgreich gelöscht.',

        'create' => [
            'title' => 'Abgleichregel hinzufügen',
        ],

        'edit' => [
            'title' => 'Abgleichregel bearbeiten',
        ],

        'form' => [
            'target'            => 'Vergleichen',
            'select-target'     => 'Wählen Sie, was verglichen wird',
            'target-info'       => 'Der Datensatzcode, seine Bezeichnung oder ein Feld dieses Entitätstyps.',
            'comparator'        => 'Vergleichsmethode',
            'select-comparator' => 'Vergleichsmethode auswählen',
            'comparator-info'   => 'Wie die beiden Werte verglichen werden, z. B. Unscharf für Namen mit Tippfehlern oder Numerisch für Beträge.',
            'weight'            => 'Gewichtung',
            'weight-info'       => 'Wie stark diese Regel zählt, von 0,1 bis 100. Die Punktzahl ist der gewichtete Durchschnitt der Regeln, für die die eingehende Zeile einen Wert hat.',
            'tolerance'         => 'Toleranz',
            'tolerance-info'    => 'Die größte Differenz zwischen zwei Zahlen, die noch als ähnlich gilt. Leer lassen, um nur gleiche Zahlen abzugleichen.',
            'days'              => 'Datumsfenster (Tage)',
            'days-info'         => 'Wie viele Tage zwei Datumsangaben höchstens auseinanderliegen dürfen, um noch als ähnlich zu gelten. Leer lassen, um nur denselben Tag abzugleichen.',
            'sort-order'        => 'Sortierreihenfolge',
            'is-blocking'       => 'Blocking-Schlüssel',
            'is-blocking-info'  => 'Sucht zusätzlich nach Datensätzen mit genau diesem Wert, auch wenn Code und Bezeichnung abweichen. Felder müssen durchsuchbar sein.',
            'save-btn'          => 'Abgleichregel speichern',
        ],

        'targets' => [
            'code'  => 'Code',
            'label' => 'Bezeichnung',
        ],

        'comparators' => [
            'exact'    => 'Exakt',
            'fuzzy'    => 'Unscharf',
            'token'    => 'Gleiche Wörter',
            'phonetic' => 'Gleicher Klang',
            'numeric'  => 'Numerisch',
            'date'     => 'Datum',
        ],

        'datagrid' => [
            'target'     => 'Vergleichen',
            'comparator' => 'Vergleichsmethode',
            'weight'     => 'Gewichtung',
            'blocking'   => 'Blocking-Schlüssel',
            'sort-order' => 'Sortierreihenfolge',
            'yes'        => 'Ja',
            'no'         => 'Nein',
            'edit'       => 'Bearbeiten',
            'delete'     => 'Löschen',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Nur der Code, die Bezeichnung oder ein durchsuchbares Feld kann ein Blocking-Schlüssel sein.',
            'too-many'                => 'Ein Entitätstyp kann höchstens :count Abgleichregeln haben.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Übernahme',
        'title'          => 'Übernahmeregeln',
        'info'           => 'Legen Sie fest, welchen Wert der Golden Record behält, wenn zwei Datensätze dieses Typs zusammengeführt werden. Ohne Regeln behält der führende Datensatz seinen Wert, sofern er nicht leer ist.',
        'create-btn'     => 'Übernahmeregel hinzufügen',
        'create-success' => 'Übernahmeregel erfolgreich erstellt.',
        'update-success' => 'Übernahmeregel erfolgreich aktualisiert.',
        'delete-success' => 'Übernahmeregel erfolgreich gelöscht.',

        'create' => [
            'title' => 'Übernahmeregel hinzufügen',
        ],

        'edit' => [
            'title' => 'Übernahmeregel bearbeiten',
        ],

        'form' => [
            'target'            => 'Gilt für',
            'select-target'     => 'Auswählen, worauf sich die Regel bezieht',
            'target-info'       => 'Ein Feld, die Bezeichnung des Datensatzes oder alle Felder ohne eigene Regel.',
            'strategy'          => 'Strategie',
            'select-strategy'   => 'Strategie auswählen',
            'strategy-info'     => 'Wie der übernommene Wert bestimmt wird. Datenverantwortliche können jede Auswahl im Zusammenführungsdialog weiterhin ändern.',
            'source-order'      => 'Quellenreihenfolge',
            'select-sources'    => 'Quellsysteme auswählen',
            'source-order-info' => 'Das vertrauenswürdigste Quellsystem zuerst. Nicht aufgeführte Systeme folgen ihrer Priorität. Leer lassen, um nur die Priorität der Quellsysteme zu verwenden.',
            'sort-order'        => 'Sortierreihenfolge',
            'save-btn'          => 'Übernahmeregel speichern',
        ],

        'targets' => [
            'default' => 'Standard für alle Felder',
            'label'   => 'Bezeichnung',
        ],

        'strategies' => [
            'source_priority' => 'Vertrauenswürdigste Quelle',
            'most_recent'     => 'Zuletzt aktualisiert',
            'most_complete'   => 'Am vollständigsten',
            'keep_target'     => 'Führenden Datensatz behalten',
            'manual'          => 'Datenverantwortlicher entscheidet',
        ],

        'datagrid' => [
            'target'     => 'Gilt für',
            'strategy'   => 'Strategie',
            'sort-order' => 'Sortierreihenfolge',
            'edit'       => 'Bearbeiten',
            'delete'     => 'Löschen',
        ],

        'validation' => [
            'target-taken' => 'Dieser Entitätstyp hat bereits eine Übernahmeregel für dieses Ziel.',
            'too-many'     => 'Ein Entitätstyp kann höchstens :count Übernahmeregeln haben.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => ':type darunter durchsuchen',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Oberste Ebene',
        ],

        'datagrid' => [
            'erase'         => 'Endgültig löschen',
            'parent'        => 'Übergeordnet',
            'top-level'     => 'Oberste Ebene',
            'children'      => 'Untergeordnet',
            'view-children' => 'Die :count Datensätze unter diesem anzeigen',
        ],

        'erase' => [
            'btn'             => 'Endgültig löschen',
            'confirm-title'   => 'Diesen Datensatz endgültig löschen?',
            'confirm-message' => 'Alle Werte, die Bezeichnung und der Code dieses Datensatzes und der darin zusammengeführten Duplikate werden entfernt, ebenso ihr Verlauf, ihre Änderungsanträge und Qualitätsprobleme. Der Datensatz bleibt als gelöschter Platzhalter erhalten, damit Quell-IDs und Verknüpfungen weiterhin aufgelöst werden. Dies kann nicht rückgängig gemacht werden.',
            'success'         => 'Datensatz endgültig gelöscht.',
            'not-retired'     => 'Nur ein stillgelegter Datensatz kann gelöscht werden. Legen Sie ihn zuerst still.',
        ],

        'mass-retire' => [
            'success' => ':count Datensatz/Datensätze stillgelegt.',
            'pending' => ':count Stilllegung(en) zur Genehmigung eingereicht.',
            'skipped' => ':count Datensatz/Datensätze übersprungen, da sie weder Entwurf noch aktiv sind oder von jemand anderem geändert wurden.',
            'none'    => 'Keiner der ausgewählten Datensätze konnte stillgelegt werden.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Freigabe erforderlich: Änderungsanfrage #:id',
            'description'           => ':requester hat eine „:operation“-Änderung an :type zur Freigabe eingereicht.',
            'description-automated' => 'Eine automatische „:operation“-Änderung an :type wartet auf Ihre Freigabe.',
        ],
    ],

    'access' => [
        'menu'           => 'Datenzugriff',
        'acl'            => [
            'index'  => 'Datenzugriff',
            'create' => 'Erstellen',
            'edit'   => 'Bearbeiten',
            'delete' => 'Löschen',
        ],
        'principals' => [
            'role' => 'Rolle',
            'user' => 'Benutzer',
        ],
        'actions' => [
            'view'    => 'Ansehen',
            'edit'    => 'Bearbeiten',
            'approve' => 'Genehmigen',
        ],
        'index' => [
            'title'      => 'Datenzugriff',
            'create-btn' => 'Berechtigung hinzufügen',
            'info'       => 'Berechtigungen legen fest, auf welche Datensätze eingeschränkter Entitätstypen jede Rolle oder jeder Benutzer zugreifen kann: den gewählten Datensatz und alles darunter. Der Zugriff aus der Rolle eines Benutzers und aus direkt erteilten Berechtigungen addiert sich.',
        ],
        'create' => [
            'title' => 'Datenzugriff-Berechtigung hinzufügen',
        ],
        'edit' => [
            'title' => 'Datenzugriff-Berechtigung bearbeiten',
        ],
        'form' => [
            'save-btn'         => 'Berechtigung speichern',
            'principal'        => 'Erteilt an',
            'principal-info'   => 'Eine Rollenberechtigung gilt für jeden Benutzer mit dieser Rolle. Für eine Ausnahme erteilen Sie sie einem einzelnen Benutzer.',
            'principal-type'   => 'Erteilen an',
            'role'             => 'Rolle',
            'user'             => 'Benutzer',
            'scope'            => 'Geltungsbereich',
            'scope-info'       => 'Die Berechtigung umfasst den gewählten Datensatz und jeden Datensatz darunter in der Hierarchie.',
            'root'             => 'Wurzel des Teilbaums',
            'root-info'        => 'Zum Beispiel ein Standort oder ein Werk. Datensätze darunter erben die Berechtigung.',
            'entity-type'      => 'Entitätstyp',
            'entity-type-info' => 'Beschränken Sie die Berechtigung auf einen eingeschränkten Entitätstyp oder lassen Sie das Feld leer für alle eingeschränkten Typen.',
            'any-entity-type'  => 'Alle eingeschränkten Entitätstypen',
            'actions'          => 'Aktionen',
            'actions-info'     => 'Ansehen liest Datensätze, Bearbeiten ändert, deaktiviert, führt zusammen und erstellt Datensätze unterhalb der Wurzel, Genehmigen entscheidet über zurückgehaltene Änderungen. Bearbeiten und Genehmigen schließen Ansehen ein.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Typ',
            'principal'         => 'Erteilt an',
            'missing-principal' => 'Gelöscht',
            'entity-type'       => 'Entitätstyp',
            'root'              => 'Wurzel des Teilbaums',
            'actions'           => 'Aktionen',
            'edit'              => 'Bearbeiten',
            'delete'            => 'Löschen',
        ],
        'role-card' => [
            'title'  => 'Stammdatenzugriff',
            'info'   => 'Auf welche Datensätze eingeschränkter Entitätstypen Benutzer mit dieser Rolle zugreifen können. Jede Berechtigung umfasst ihren Wurzeldatensatz und alles darunter.',
            'empty'  => 'Diese Rolle hat noch keine Stammdatenzugriff-Berechtigungen.',
            'remove' => 'Entfernen',
            'add'    => 'Berechtigung hinzufügen',
        ],
        'entity-type' => [
            'is-scoped'      => 'Zugriff nach Teilbaum einschränken',
            'is-scoped-info' => 'Wenn aktiviert, greifen Benutzer nur auf Datensätze dieses Typs innerhalb der Teilbäume zu, die ihrer Rolle oder ihnen selbst auf dem Bildschirm „Datenzugriff“ erteilt wurden. Für gemeinsame Referenzdaten deaktiviert lassen.',
        ],
        'validation' => [
            'record-not-found'    => 'Wählen Sie einen Datensatz, den Sie sehen können, als Wurzel des Teilbaums.',
            'parent-out-of-scope' => 'Wählen Sie ein übergeordnetes Element in einem Teilbaum, in dem Sie Bearbeitungszugriff haben.',
        ],
        'create-success' => 'Datenzugriff-Berechtigung hinzugefügt.',
        'update-success' => 'Datenzugriff-Berechtigung aktualisiert.',
        'delete-success' => 'Datenzugriff-Berechtigung gelöscht.',
    ],

    'install' => [
        'migrating' => 'Die Stammdaten-Migrationen werden ausgeführt.',
        'demo'      => 'Die Demodaten für die Fertigung werden installiert.',
        'done'      => 'Die Stammdatenverwaltung ist installiert. Aktive Module: :modules.',
    ],
];
