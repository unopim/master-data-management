<?php

return [
    'menu' => [
        'master-data' => 'Dane podstawowe',
        'icons'       => 'Ikony',
    ],

    'acl' => [
        'master-data' => 'Dane podstawowe',
        'icons'       => [
            'index'  => 'Ikony',
            'create' => 'Utwórz',
            'edit'   => 'Edytuj',
            'delete' => 'Usuń',
        ],

        'records' => [
            'erase' => 'Usuń trwale',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dane podstawowe',
        'inside'     => 'W obrębie: :types',
        'top-level'  => 'Najwyższy poziom',
        'standalone' => 'Samodzielny',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dane podstawowe',
            'records'     => 'Rekordy',
            'upsert'      => 'Wstaw lub zaktualizuj według ID systemu źródłowego',
        ],

        'validation-failed'     => 'Walidacja nie powiodła się.',
        'change-held'           => 'Zmiana oczekuje na zatwierdzenie.',
        'xref-not-found'        => 'Żaden rekord nie pasuje do tego zewnętrznego identyfikatora.',
        'entity-type-not-found' => 'Nie znaleziono typu encji.',
        'record-not-found'      => 'Nie znaleziono rekordu.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Typ encji',
            'source-system'       => 'System źródłowy',
            'code-list'           => 'Lista kodów',
            'entity-type-info'    => 'Typ encji, której rekordy zawiera plik.',
            'source-system-info'  => 'System, z którego pochodzi plik. Jego zewnętrzne identyfikatory są odczytywane z kolumny external_id.',
            'code-list-info'      => 'Lista, do której następuje import. Kody są zapisywane w jej wersji roboczej, którą publikujesz osobno.',
            'mapping-info'        => 'Jeden wiersz na każdą przemianowaną kolumnę: kolumna pliku=kod pola lub kod pola-język dla pola zależnego od języka, np. Asset Num=external_id i Description=name. Kolumny bez wiersza są odczytywane pod własną nazwą.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Wybierz aktywny typ encji, którego rekordy możesz tworzyć lub edytować.',
            'source-system-invalid'              => 'Wybierz aktywny system źródłowy.',
            'code-list-invalid'                  => 'Wybierz istniejącą listę kodów, którą możesz edytować.',
            'external-id-without-source-system'  => 'Plik zawiera kolumnę zewnętrznych identyfikatorów (:column). Wybierz system źródłowy, z którego pochodzi, albo usuń kolumnę.',
        ],

        'schedule' => [
            'job-not-found'  => 'Żadne zadanie importu nie ma kodu :code.',
            'not-masterdata' => 'Zadanie :code nie jest importem danych podstawowych.',
            'no-user'        => 'Wskaż administratora, w imieniu którego działa import, za pomocą --user lub MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Żaden aktywny administrator nie ma adresu e-mail :email.',
            'file-not-found' => 'Nie znaleziono pliku :path na dysku :disk.',
            'nothing'        => 'Żaden plik nie oczekuje w :path na dysku :disk.',
            'queued'         => 'Dodano plik :file do kolejki dla zadania :code, śledzone jako #:id.',
            'imported'       => 'Zaimportowano plik :file dla zadania :code, śledzone jako #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demo produkcyjne gotowe: :types typów encji, :systems systemów źródłowych, :records rekordów w drzewie zakładów, :xrefs odwołań krzyżowych, :lists list kodów, :fields pól, :aliases aliasów.',
        'invalid-template' => 'Nie można odczytać szablonu :name lub ma on nieobsługiwaną wersję formatu.',
    ],

    'platform-module' => [
        'name' => 'Dane podstawowe (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Usunięte typy encji: :count.',
        'mass-delete-skipped' => 'Nie usunięto, ponieważ nadal zawierają rekordy: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Typy nadrzędne',
            'records-count'        => 'Rekordy',
            'fields-count'         => 'Pola',
            'updated-at'           => 'Zaktualizowano',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Kolejność',
        ],

        'edit' => [
            'schema-version' => 'Schemat v:version',
        ],

        'form' => [
            'basics'                    => 'Podstawy',
            'identity'                  => 'Identyfikacja',
            'hierarchy'                 => 'Hierarchia',
            'governance'                => 'Zarządzanie',
            'quality-matching'          => 'Jakość i dopasowanie',
            'matching-info'             => 'Przychodzące wiersze z systemów źródłowych są dopasowywane do istniejących rekordów w skrzynce dopasowań.',
            'auto-match-threshold'      => 'Próg automatycznego dopasowania',
            'auto-match-off'            => 'Wyłączone',
            'auto-match-threshold-info' => 'Pozostaw puste, aby przeglądać każdy wiersz. Od 0,5 do 1: wiersz, którego najlepsza sugestia osiąga co najmniej tę wartość, a żadna inna nie jest równie wysoka, zostaje zaakceptowany automatycznie. Typy wymagające zatwierdzenia nadal wstrzymują powiązanie do zatwierdzenia.',
            'match-on-write'            => 'Sprawdzaj duplikaty przy zapisie',
            'match-on-write-info'       => 'Każdy zapisany rekord jest w tle porównywany z pozostałymi rekordami według reguł dopasowania. Wyłącz, aby wyszukiwać duplikaty tylko podczas skanów jakości.',
            'manage-icons'              => 'Zarządzaj ikonami',
            'sort-order'                => 'Kolejność',
            'sort-order-info'           => 'Niższe numery są wyświetlane jako pierwsze, od 0 do 9999; typy z tym samym numerem są sortowane według kodu.',
        ],

        'quick-create' => [
            'code-info'             => 'Małe litery, cyfry i podkreślenia, zaczynając od litery. Nie można zmienić po zapisaniu.',
            'code-pattern-info'     => 'Użyj {seq} lub {seq:6} dla numeru kolejnego i {TYPE} dla kodu typu, np. {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Rekordy pochodzą z wielu systemów źródłowych i są dopasowywane oraz scalane w jeden złoty rekord.',
                'centralized'   => 'Rekordy są tworzone i utrzymywane tutaj, a następnie publikowane w systemach, które z nich korzystają.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Reguła dopasowania dla :target',
        ],

        'show' => [
            'auto-matched' => 'Dopasowano automatycznie',
            'auto-pending' => 'Dopasowano automatycznie; powiązanie czeka na zatwierdzenie.',
        ],

        'datagrid' => [
            'decision' => 'Decyzja',
            'auto'     => 'Dopasowano automatycznie',
            'manual'   => 'Ręcznie',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Używane przez',
            'empty'        => 'Żadne zadanie eksportu nie publikuje rekordów tego typu i statusu.',
            'job'          => 'Zadanie eksportu',
            'format'       => 'Format',
            'mode'         => 'Tryb',
            'full'         => 'Pełny',
            'delta'        => 'Tylko zmiany',
            'last-run-at'  => 'Ostatnie zakończone uruchomienie',
            'no-run'       => 'Nigdy nie uruchomiono',
            'record-state' => 'Ten rekord',
            'states'       => [
                'never_run'  => 'Jeszcze nie wyeksportowano',
                'pending'    => 'Zmieniono od ostatniego uruchomienia',
                'included'   => 'Uwzględniono w ostatnim uruchomieniu',
                'up_to_date' => 'Aktualny',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ikona została utworzona.',
        'update-success' => 'Ikona została zaktualizowana.',
        'delete-success' => 'Ikona została usunięta.',
        'delete-in-use'  => '{1} Ta ikona jest używana przez jeden typ encji i nie można jej usunąć.|[2,*] Ta ikona jest używana przez następującą liczbę typów encji: :count, i nie można jej usunąć.',
        'index'          => [
            'title'      => 'Ikony',
            'create-btn' => 'Dodaj ikonę',
        ],

        'create' => [
            'title'    => 'Dodaj ikonę',
            'save-btn' => 'Zapisz ikonę',
        ],

        'edit' => [
            'title'    => 'Edytuj ikonę',
            'save-btn' => 'Zapisz ikonę',
        ],

        'form' => [
            'code'      => 'Kod',
            'name'      => 'Nazwa',
            'file'      => 'Plik ikony',
            'file-info' => 'SVG, PNG lub WebP, do :size KB. Użyj jednego kształtu na przezroczystym tle: przyjmie kolor motywu.',
            'preview'   => 'Podgląd',
            'usage'     => '{0} Żaden typ encji nie używa jeszcze tej ikony.|{1} Używana przez jeden typ encji.|[2,*] Używana przez typy encji: :count.',
        ],

        'library' => [
            'barcode'    => 'Kod kreskowy',
            'boxes'      => 'Materiały',
            'briefcase'  => 'Dostawca usług',
            'building_2' => 'Firma',
            'cpu'        => 'Środek trwały',
            'factory'    => 'Zakład',
            'file_text'  => 'Umowa',
            'globe'      => 'Kraj',
            'handshake'  => 'Partner',
            'landmark'   => 'Podmiot prawny',
            'map_pin'    => 'Lokalizacja',
            'network'    => 'Hierarchia',
            'package'    => 'Produkt',
            'store'      => 'Sklep',
            'tag'        => 'Kategoria',
            'truck'      => 'Dostawca',
            'user_round' => 'Kontakt',
            'users'      => 'Klient',
            'warehouse'  => 'Magazyn',
            'wrench'     => 'Wyposażenie',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ikona',
            'code'    => 'Kod',
            'name'    => 'Nazwa',
            'usage'   => 'Używana przez',
            'edit'    => 'Edytuj',
            'delete'  => 'Usuń',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Dopasowanie',
        'title'          => 'Reguły dopasowania',
        'info'           => 'Określ, jak porównywane są rekordy tego typu, aby wykrywać duplikaty i dopasowywać przychodzące wiersze. Bez reguł porównywane są kod i etykieta.',
        'create-btn'     => 'Dodaj regułę dopasowania',
        'create-success' => 'Reguła dopasowania została utworzona.',
        'update-success' => 'Reguła dopasowania została zaktualizowana.',
        'delete-success' => 'Reguła dopasowania została usunięta.',

        'create' => [
            'title' => 'Dodaj regułę dopasowania',
        ],

        'edit' => [
            'title' => 'Edytuj regułę dopasowania',
        ],

        'form' => [
            'target'            => 'Porównaj',
            'select-target'     => 'Wybierz, co porównać',
            'target-info'       => 'Kod rekordu, jego etykieta lub pole tego typu encji.',
            'comparator'        => 'Metoda porównania',
            'select-comparator' => 'Wybierz metodę porównania',
            'comparator-info'   => 'Sposób porównania dwóch wartości, np. Przybliżone dla nazw z literówkami lub Liczbowe dla kwot.',
            'weight'            => 'Waga',
            'weight-info'       => 'Jak bardzo liczy się ta reguła, od 0,1 do 100. Wynik to średnia ważona reguł, dla których przychodzący wiersz ma wartość.',
            'tolerance'         => 'Tolerancja',
            'tolerance-info'    => 'Największa różnica między dwiema liczbami, przy której nadal uznaje się je za podobne. Pozostaw puste, aby dopasowywać tylko równe liczby.',
            'days'              => 'Okno dat (dni)',
            'days-info'         => 'Maksymalna liczba dni między dwiema datami, przy której nadal uznaje się je za podobne. Pozostaw puste, aby dopasowywać tylko ten sam dzień.',
            'sort-order'        => 'Kolejność',
            'is-blocking'       => 'Klucz blokujący',
            'is-blocking-info'  => 'Wyszukuje także rekordy o dokładnie tej samej wartości, nawet jeśli ich kod i etykieta się różnią. Pola muszą być wyszukiwalne.',
            'save-btn'          => 'Zapisz regułę dopasowania',
        ],

        'targets' => [
            'code'  => 'Kod',
            'label' => 'Etykieta',
        ],

        'comparators' => [
            'exact'    => 'Dokładne',
            'fuzzy'    => 'Przybliżone',
            'token'    => 'Te same słowa',
            'phonetic' => 'Podobne brzmienie',
            'numeric'  => 'Liczbowe',
            'date'     => 'Data',
        ],

        'datagrid' => [
            'target'     => 'Porównaj',
            'comparator' => 'Metoda porównania',
            'weight'     => 'Waga',
            'blocking'   => 'Klucz blokujący',
            'sort-order' => 'Kolejność',
            'yes'        => 'Tak',
            'no'         => 'Nie',
            'edit'       => 'Edytuj',
            'delete'     => 'Usuń',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Kluczem blokującym może być tylko kod, etykieta lub pole wyszukiwalne.',
            'too-many'                => 'Typ encji może mieć najwyżej :count reguł dopasowania.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Priorytet wartości',
        'title'          => 'Reguły priorytetu wartości',
        'info'           => 'Określ, jaką wartość zachowuje rekord wzorcowy po scaleniu dwóch rekordów tego typu. Bez reguł rekord docelowy zachowuje swoją wartość, chyba że jest pusta.',
        'create-btn'     => 'Dodaj regułę priorytetu wartości',
        'create-success' => 'Reguła priorytetu wartości została utworzona.',
        'update-success' => 'Reguła priorytetu wartości została zaktualizowana.',
        'delete-success' => 'Reguła priorytetu wartości została usunięta.',

        'create' => [
            'title' => 'Dodaj regułę priorytetu wartości',
        ],

        'edit' => [
            'title' => 'Edytuj regułę priorytetu wartości',
        ],

        'form' => [
            'target'            => 'Dotyczy',
            'select-target'     => 'Wybierz, czego dotyczy reguła',
            'target-info'       => 'Pole, etykieta rekordu lub wszystkie pola bez własnej reguły.',
            'strategy'          => 'Strategia',
            'select-strategy'   => 'Wybierz strategię',
            'strategy-info'     => 'Jak wybierana jest zachowywana wartość. Opiekunowie danych nadal mogą zmienić każdy wybór na ekranie scalania.',
            'source-order'      => 'Kolejność źródeł',
            'select-sources'    => 'Wybierz systemy źródłowe',
            'source-order-info' => 'Najbardziej zaufany system źródłowy jako pierwszy. Pominięte systemy podlegają swojemu priorytetowi. Zostaw puste, aby użyć wyłącznie priorytetu systemów źródłowych.',
            'sort-order'        => 'Kolejność',
            'save-btn'          => 'Zapisz regułę priorytetu wartości',
        ],

        'targets' => [
            'default' => 'Domyślnie dla wszystkich pól',
            'label'   => 'Etykieta',
        ],

        'strategies' => [
            'source_priority' => 'Najbardziej zaufane źródło',
            'most_recent'     => 'Ostatnio zaktualizowane',
            'most_complete'   => 'Najbardziej kompletne',
            'keep_target'     => 'Zachowaj rekord docelowy',
            'manual'          => 'Decyduje opiekun danych',
        ],

        'datagrid' => [
            'target'     => 'Dotyczy',
            'strategy'   => 'Strategia',
            'sort-order' => 'Kolejność',
            'edit'       => 'Edytuj',
            'delete'     => 'Usuń',
        ],

        'validation' => [
            'target-taken' => 'Ten typ encji ma już regułę priorytetu wartości dla tego celu.',
            'too-many'     => 'Typ encji może mieć najwyżej :count reguł priorytetu wartości.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Przeglądaj :type poniżej',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Najwyższy poziom',
        ],

        'datagrid' => [
            'erase'         => 'Usuń trwale',
            'parent'        => 'Nadrzędny',
            'top-level'     => 'Najwyższy poziom',
            'children'      => 'Podrzędne',
            'view-children' => 'Pokaż rekordy poniżej tego (:count)',
        ],

        'erase' => [
            'btn'             => 'Usuń trwale',
            'confirm-title'   => 'Trwale usunąć ten rekord?',
            'confirm-message' => 'Wszystkie wartości, etykieta i kod tego rekordu oraz scalonych z nim duplikatów zostaną usunięte wraz z ich historią, wnioskami o zmianę i problemami jakości. Rekord pozostaje jako usunięty symbol zastępczy, aby jego identyfikatory źródłowe i powiązania nadal działały. Tej operacji nie można cofnąć.',
            'success'         => 'Rekord został trwale usunięty.',
            'not-retired'     => 'Można usunąć tylko wycofany rekord. Najpierw go wycofaj.',
        ],

        'mass-retire' => [
            'success' => 'Wycofano rekordy: :count.',
            'pending' => 'Wysłano do zatwierdzenia wycofania: :count.',
            'skipped' => 'Pominięto rekordy: :count, ponieważ nie są szkicami ani aktywne lub zostały zmienione przez kogoś innego.',
            'none'    => 'Nie udało się wycofać żadnego z wybranych rekordów.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Wymagane zatwierdzenie: prośba o zmianę #:id',
            'description'           => ':requester przesłał(a) zmianę „:operation” w :type do Twojego zatwierdzenia.',
            'description-automated' => 'Automatyczna zmiana „:operation” w :type czeka na Twoje zatwierdzenie.',
        ],
    ],

    'access' => [
        'menu'           => 'Dostęp do danych',
        'acl'            => [
            'index'  => 'Dostęp do danych',
            'create' => 'Utwórz',
            'edit'   => 'Edytuj',
            'delete' => 'Usuń',
        ],
        'principals' => [
            'role' => 'Rola',
            'user' => 'Użytkownik',
        ],
        'actions' => [
            'view'    => 'Wyświetlanie',
            'edit'    => 'Edycja',
            'approve' => 'Zatwierdzanie',
        ],
        'index' => [
            'title'      => 'Dostęp do danych',
            'create-btn' => 'Dodaj uprawnienie',
            'info'       => 'Uprawnienia określają, do których rekordów ograniczonych typów encji ma dostęp każda rola lub użytkownik: wybranego rekordu i wszystkiego pod nim. Dostęp wynikający z roli użytkownika oraz z uprawnień nadanych użytkownikowi sumuje się.',
        ],
        'create' => [
            'title' => 'Dodaj uprawnienie dostępu do danych',
        ],
        'edit' => [
            'title' => 'Edytuj uprawnienie dostępu do danych',
        ],
        'form' => [
            'save-btn'         => 'Zapisz uprawnienie',
            'principal'        => 'Nadano',
            'principal-info'   => 'Uprawnienie roli obejmuje każdego użytkownika z tą rolą. Nadaj je pojedynczemu użytkownikowi, aby uzyskać wyjątek.',
            'principal-type'   => 'Nadaj dla',
            'role'             => 'Rola',
            'user'             => 'Użytkownik',
            'scope'            => 'Zakres',
            'scope-info'       => 'Uprawnienie obejmuje wybrany rekord i każdy rekord pod nim w hierarchii.',
            'root'             => 'Korzeń poddrzewa',
            'root-info'        => 'Na przykład lokalizacja lub zakład. Rekordy pod nim dziedziczą uprawnienie.',
            'entity-type'      => 'Typ encji',
            'entity-type-info' => 'Ogranicz uprawnienie do jednego ograniczonego typu encji lub pozostaw puste dla wszystkich ograniczonych typów.',
            'any-entity-type'  => 'Wszystkie ograniczone typy encji',
            'actions'          => 'Akcje',
            'actions-info'     => 'Wyświetlanie odczytuje rekordy, Edycja zmienia, wycofuje, scala i tworzy rekordy pod korzeniem, Zatwierdzanie rozstrzyga wstrzymane zmiany. Edycja i Zatwierdzanie obejmują Wyświetlanie.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Typ',
            'principal'         => 'Nadano',
            'missing-principal' => 'Usunięto',
            'entity-type'       => 'Typ encji',
            'root'              => 'Korzeń poddrzewa',
            'actions'           => 'Akcje',
            'edit'              => 'Edytuj',
            'delete'            => 'Usuń',
        ],
        'role-card' => [
            'title'  => 'Dostęp do danych głównych',
            'info'   => 'Do których rekordów ograniczonych typów encji mają dostęp użytkownicy z tą rolą. Każde uprawnienie obejmuje swój rekord główny i wszystko pod nim.',
            'empty'  => 'Ta rola nie ma jeszcze uprawnień dostępu do danych głównych.',
            'remove' => 'Usuń',
            'add'    => 'Dodaj uprawnienie',
        ],
        'entity-type' => [
            'is-scoped'      => 'Ogranicz dostęp według poddrzewa',
            'is-scoped-info' => 'Po włączeniu użytkownicy mają dostęp tylko do rekordów tego typu w poddrzewach nadanych ich roli lub im samym na ekranie Dostęp do danych. Pozostaw wyłączone dla współdzielonych danych referencyjnych.',
        ],
        'validation' => [
            'record-not-found'    => 'Wybierz rekord, który możesz zobaczyć, jako korzeń poddrzewa.',
            'parent-out-of-scope' => 'Wybierz element nadrzędny w poddrzewie, w którym masz dostęp do edycji.',
        ],
        'create-success' => 'Dodano uprawnienie dostępu do danych.',
        'update-success' => 'Zaktualizowano uprawnienie dostępu do danych.',
        'delete-success' => 'Usunięto uprawnienie dostępu do danych.',
    ],

    'install' => [
        'migrating' => 'Uruchamianie migracji danych głównych.',
        'demo'      => 'Instalowanie danych demonstracyjnych dla produkcji.',
        'done'      => 'Zarządzanie danymi głównymi zostało zainstalowane. Aktywne moduły: :modules.',
    ],
];
