<?php

return [
    'menu' => [
        'master-data' => 'Données de référence',
        'icons'       => 'Icônes',
    ],

    'acl' => [
        'master-data' => 'Données de référence',
        'icons'       => [
            'index'  => 'Icônes',
            'create' => 'Créer',
            'edit'   => 'Modifier',
            'delete' => 'Supprimer',
        ],

        'records' => [
            'erase' => 'Effacer définitivement',
        ],
    ],

    'dashboard' => [
        'title'      => 'Données de référence',
        'inside'     => 'Dans : :types',
        'top-level'  => 'Niveau supérieur',
        'standalone' => 'Autonome',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Données de référence',
            'records'     => 'Enregistrements',
            'upsert'      => 'Insérer ou mettre à jour selon l\'ID du système source',
        ],

        'validation-failed'     => 'Échec de la validation.',
        'change-held'           => 'La modification est en attente d\'approbation.',
        'xref-not-found'        => 'Aucun enregistrement ne correspond à cet identifiant externe.',
        'entity-type-not-found' => 'Le type d\'entité est introuvable.',
        'record-not-found'      => 'L\'enregistrement est introuvable.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Type d\'entité',
            'source-system'       => 'Système source',
            'code-list'           => 'Liste de codes',
            'entity-type-info'    => 'Le type d\'entité dont le fichier contient les enregistrements.',
            'source-system-info'  => 'Le système dont provient le fichier. Ses identifiants externes sont lus dans la colonne external_id.',
            'code-list-info'      => 'La liste dans laquelle importer. Les codes sont écrits dans sa version brouillon, que vous publiez séparément.',
            'mapping-info'        => 'Une ligne par colonne renommée : colonne du fichier=code du champ, ou code du champ-langue pour un champ par langue, par ex. Asset Num=external_id et Description=name. Les colonnes sans ligne sont lues sous leur propre nom.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Choisissez un type d\'entité actif dont vous pouvez créer ou modifier les enregistrements.',
            'source-system-invalid'              => 'Choisissez un système source actif.',
            'code-list-invalid'                  => 'Choisissez une liste de codes existante que vous pouvez modifier.',
            'external-id-without-source-system'  => 'Le fichier contient une colonne d\'identifiant externe (:column). Choisissez le système source dont il provient, ou supprimez la colonne.',
        ],

        'schedule' => [
            'job-not-found'  => 'Aucune tâche d\'import n\'a le code :code.',
            'not-masterdata' => 'La tâche :code n\'est pas un import de données de référence.',
            'no-user'        => 'Indiquez l\'administrateur sous lequel l\'import s\'exécute avec --user ou MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Aucun administrateur actif n\'a l\'adresse e-mail :email.',
            'file-not-found' => 'Le fichier :path est introuvable sur le disque :disk.',
            'nothing'        => 'Aucun fichier n\'est en attente dans :path sur le disque :disk.',
            'queued'         => 'Fichier :file mis en file d\'attente pour la tâche :code, suivi sous #:id.',
            'imported'       => 'Fichier :file importé pour la tâche :code, suivi sous #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Démo de fabrication prête : :types types d\'entité, :systems systèmes sources, :records enregistrements dans une arborescence de sites, :xrefs références croisées, :lists listes de codes, :fields champs, :aliases alias.',
        'invalid-template' => 'Le modèle :name est illisible ou sa version de format n\'est pas prise en charge.',
    ],

    'platform-module' => [
        'name' => 'Données de référence (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Types d\'entité supprimés : :count.',
        'mass-delete-skipped' => 'Non supprimés car ils contiennent encore des enregistrements : :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Types parents',
            'records-count'        => 'Enregistrements',
            'fields-count'         => 'Champs',
            'updated-at'           => 'Mis à jour',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordre d’affichage',
        ],

        'edit' => [
            'schema-version' => 'Schéma v:version',
        ],

        'form' => [
            'basics'                    => 'Informations de base',
            'identity'                  => 'Identité',
            'hierarchy'                 => 'Hiérarchie',
            'governance'                => 'Gouvernance',
            'quality-matching'          => 'Qualité et rapprochement',
            'matching-info'             => 'Les lignes entrantes des systèmes sources sont rapprochées des enregistrements existants dans la boîte de rapprochement.',
            'auto-match-threshold'      => 'Seuil de rapprochement automatique',
            'auto-match-off'            => 'Désactivé',
            'auto-match-threshold-info' => 'Laissez vide pour examiner chaque ligne. De 0,5 à 1 : une ligne dont la meilleure suggestion atteint au moins ce score, sans autre suggestion aussi élevée, est acceptée automatiquement. Les types soumis à approbation conservent le lien en attente d\'approbation.',
            'match-on-write'            => 'Rechercher les doublons à l\'enregistrement',
            'match-on-write-info'       => 'Chaque enregistrement sauvegardé est comparé aux autres en arrière-plan à l\'aide des règles de rapprochement. Désactivez pour ne détecter les doublons que lors des analyses de qualité.',
            'manage-icons'              => 'Gérer les icônes',
            'sort-order'                => 'Ordre d’affichage',
            'sort-order-info'           => 'Les numéros les plus petits s’affichent en premier, de 0 à 9999 ; les types de même numéro sont classés par code.',
        ],

        'quick-create' => [
            'code-info'             => 'Lettres minuscules, chiffres et tirets bas, en commençant par une lettre. Non modifiable après l\'enregistrement.',
            'code-pattern-info'     => 'Utilisez {seq} ou {seq:6} pour le numéro séquentiel et {TYPE} pour le code du type, par exemple {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Les enregistrements proviennent de plusieurs systèmes sources et sont rapprochés puis fusionnés en un enregistrement de référence unique.',
                'centralized'   => 'Les enregistrements sont créés et gérés ici, puis publiés vers les systèmes qui les utilisent.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Règle de rapprochement sur :target',
        ],

        'show' => [
            'auto-matched' => 'Rapproché automatiquement',
            'auto-pending' => 'Rapproché automatiquement ; le lien attend une approbation.',
        ],

        'datagrid' => [
            'decision' => 'Décision',
            'auto'     => 'Rapproché automatiquement',
            'manual'   => 'Manuel',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Consommé par',
            'empty'        => 'Aucune tâche d\'export ne publie d\'enregistrements de ce type et de ce statut.',
            'job'          => 'Tâche d\'export',
            'format'       => 'Format',
            'mode'         => 'Mode',
            'full'         => 'Complet',
            'delta'        => 'Modifications uniquement',
            'last-run-at'  => 'Dernière exécution terminée',
            'no-run'       => 'Jamais exécutée',
            'record-state' => 'Cet enregistrement',
            'states'       => [
                'never_run'  => 'Pas encore exporté',
                'pending'    => 'Modifié depuis la dernière exécution',
                'included'   => 'Inclus dans la dernière exécution',
                'up_to_date' => 'À jour',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Icône créée avec succès.',
        'update-success' => 'Icône mise à jour avec succès.',
        'delete-success' => 'Icône supprimée avec succès.',
        'delete-in-use'  => '{1} Cette icône est affichée par un type d\'entité et ne peut pas être supprimée.|[2,*] Cette icône est affichée par :count types d\'entité et ne peut pas être supprimée.',
        'index'          => [
            'title'      => 'Icônes',
            'create-btn' => 'Ajouter une icône',
        ],

        'create' => [
            'title'    => 'Ajouter une icône',
            'save-btn' => 'Enregistrer l\'icône',
        ],

        'edit' => [
            'title'    => 'Modifier l\'icône',
            'save-btn' => 'Enregistrer l\'icône',
        ],

        'form' => [
            'code'      => 'Code',
            'name'      => 'Nom',
            'file'      => 'Fichier d\'icône',
            'file-info' => 'SVG, PNG ou WebP, jusqu\'à :size Ko. Utilisez une seule forme sur fond transparent : elle prend la couleur du thème.',
            'preview'   => 'Aperçu',
            'usage'     => '{0} Aucun type d\'entité n\'affiche encore cette icône.|{1} Affichée par un type d\'entité.|[2,*] Affichée par :count types d\'entité.',
        ],

        'library' => [
            'barcode'    => 'Code-barres',
            'boxes'      => 'Matériaux',
            'briefcase'  => 'Fournisseur',
            'building_2' => 'Société',
            'cpu'        => 'Actif',
            'factory'    => 'Usine',
            'file_text'  => 'Contrat',
            'globe'      => 'Pays',
            'handshake'  => 'Partenaire',
            'landmark'   => 'Entité juridique',
            'map_pin'    => 'Emplacement',
            'network'    => 'Hiérarchie',
            'package'    => 'Produit',
            'store'      => 'Magasin',
            'tag'        => 'Catégorie',
            'truck'      => 'Prestataire',
            'user_round' => 'Contact',
            'users'      => 'Client',
            'warehouse'  => 'Entrepôt',
            'wrench'     => 'Équipement',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icône',
            'code'    => 'Code',
            'name'    => 'Nom',
            'usage'   => 'Utilisée par',
            'edit'    => 'Modifier',
            'delete'  => 'Supprimer',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Rapprochement',
        'title'          => 'Règles de rapprochement',
        'info'           => 'Définissez comment les enregistrements de ce type sont comparés pour détecter les doublons et rapprocher les lignes entrantes. Sans règle, le code et le libellé sont comparés.',
        'create-btn'     => 'Ajouter une règle de rapprochement',
        'create-success' => 'Règle de rapprochement créée avec succès.',
        'update-success' => 'Règle de rapprochement mise à jour avec succès.',
        'delete-success' => 'Règle de rapprochement supprimée avec succès.',

        'create' => [
            'title' => 'Ajouter une règle de rapprochement',
        ],

        'edit' => [
            'title' => 'Modifier la règle de rapprochement',
        ],

        'form' => [
            'target'            => 'Comparer',
            'select-target'     => 'Sélectionnez ce qu\'il faut comparer',
            'target-info'       => 'Le code de l\'enregistrement, son libellé ou un champ de ce type d\'entité.',
            'comparator'        => 'Comparateur',
            'select-comparator' => 'Sélectionnez un comparateur',
            'comparator-info'   => 'Manière de comparer les deux valeurs, par ex. Approximatif pour des noms avec des fautes de frappe ou Numérique pour des montants.',
            'weight'            => 'Poids',
            'weight-info'       => 'Importance de cette règle, de 0,1 à 100. Le score est la moyenne pondérée des règles pour lesquelles la ligne entrante a une valeur.',
            'tolerance'         => 'Tolérance',
            'tolerance-info'    => 'Écart maximal entre deux nombres pour qu\'ils soient encore considérés comme similaires. Laissez vide pour ne rapprocher que des nombres égaux.',
            'days'              => 'Fenêtre de dates (jours)',
            'days-info'         => 'Nombre maximal de jours d\'écart entre deux dates pour qu\'elles soient encore considérées comme similaires. Laissez vide pour ne rapprocher que le même jour.',
            'sort-order'        => 'Ordre d’affichage',
            'is-blocking'       => 'Clé de blocage',
            'is-blocking-info'  => 'Recherche aussi les enregistrements qui partagent exactement cette valeur, même si leur code et leur libellé diffèrent. Les champs doivent être recherchables.',
            'save-btn'          => 'Enregistrer la règle de rapprochement',
        ],

        'targets' => [
            'code'  => 'Code',
            'label' => 'Libellé',
        ],

        'comparators' => [
            'exact'    => 'Exact',
            'fuzzy'    => 'Approximatif',
            'token'    => 'Mêmes mots',
            'phonetic' => 'Même prononciation',
            'numeric'  => 'Numérique',
            'date'     => 'Date',
        ],

        'datagrid' => [
            'target'     => 'Comparer',
            'comparator' => 'Comparateur',
            'weight'     => 'Poids',
            'blocking'   => 'Clé de blocage',
            'sort-order' => 'Ordre d’affichage',
            'yes'        => 'Oui',
            'no'         => 'Non',
            'edit'       => 'Modifier',
            'delete'     => 'Supprimer',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Seuls le code, le libellé ou un champ recherchable peuvent servir de clé de blocage.',
            'too-many'                => 'Un type d\'entité peut avoir au plus :count règles de rapprochement.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Valeur retenue',
        'title'          => 'Règles de valeur retenue',
        'info'           => 'Définissez quelle valeur l\'enregistrement de référence conserve lorsque deux enregistrements de ce type sont fusionnés. Sans règle, l\'enregistrement survivant conserve sa valeur sauf si elle est vide.',
        'create-btn'     => 'Ajouter une règle de valeur retenue',
        'create-success' => 'Règle de valeur retenue créée avec succès.',
        'update-success' => 'Règle de valeur retenue mise à jour avec succès.',
        'delete-success' => 'Règle de valeur retenue supprimée avec succès.',

        'create' => [
            'title' => 'Ajouter une règle de valeur retenue',
        ],

        'edit' => [
            'title' => 'Modifier la règle de valeur retenue',
        ],

        'form' => [
            'target'            => 'S\'applique à',
            'select-target'     => 'Sélectionnez à quoi la règle s\'applique',
            'target-info'       => 'Un champ, le libellé de l\'enregistrement, ou tous les champs sans règle propre.',
            'strategy'          => 'Stratégie',
            'select-strategy'   => 'Sélectionnez une stratégie',
            'strategy-info'     => 'Comment la valeur retenue est choisie. Les référents peuvent toujours modifier chaque choix sur l\'écran de fusion.',
            'source-order'      => 'Ordre des sources',
            'select-sources'    => 'Sélectionnez des systèmes source',
            'source-order-info' => 'Le système source le plus fiable en premier. Les systèmes omis suivent leur priorité. Laissez vide pour utiliser uniquement la priorité des systèmes source.',
            'sort-order'        => 'Ordre d’affichage',
            'save-btn'          => 'Enregistrer la règle de valeur retenue',
        ],

        'targets' => [
            'default' => 'Par défaut pour tous les champs',
            'label'   => 'Libellé',
        ],

        'strategies' => [
            'source_priority' => 'Source la plus fiable',
            'most_recent'     => 'Mise à jour la plus récente',
            'most_complete'   => 'La plus complète',
            'keep_target'     => 'Conserver l\'enregistrement survivant',
            'manual'          => 'Décision du référent',
        ],

        'datagrid' => [
            'target'     => 'S\'applique à',
            'strategy'   => 'Stratégie',
            'sort-order' => 'Ordre d’affichage',
            'edit'       => 'Modifier',
            'delete'     => 'Supprimer',
        ],

        'validation' => [
            'target-taken' => 'Ce type d\'entité possède déjà une règle de valeur retenue pour cette cible.',
            'too-many'     => 'Un type d\'entité peut avoir au plus :count règles de valeur retenue.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validation',
        'title'          => 'Règles de validation',
        'info'           => 'Contrôlez les valeurs des champs au-delà de leur type. Les règles bloquantes refusent un enregistrement, un import ou une écriture via l’API ; les règles d’avertissement les laissent passer et déclenchent un avertissement de qualité des données.',
        'create-btn'     => 'Ajouter une règle de validation',
        'create-success' => 'Règle de validation créée avec succès.',
        'update-success' => 'Règle de validation mise à jour avec succès.',
        'delete-success' => 'Règle de validation supprimée avec succès.',

        'create' => [
            'title' => 'Ajouter une règle de validation',
        ],

        'edit' => [
            'title' => 'Modifier la règle de validation',
        ],

        'form' => [
            'field'            => 'Champ',
            'select-field'     => 'Sélectionner un champ',
            'type'             => 'Règle',
            'select-type'      => 'Sélectionner une règle',
            'severity'         => 'Gravité',
            'severity-info'    => '« Bloquer » refuse les valeurs qui enfreignent la règle. « Avertir » les enregistre et signale un avertissement de qualité.',
            'pattern'          => 'Motif',
            'pattern-info'     => 'Une expression régulière avec délimiteurs, par exemple /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Valeur',
            'min-length'       => 'Longueur minimale',
            'max-length'       => 'Longueur maximale',
            'operator'         => 'Comparaison',
            'other-field'      => 'Comparé à',
            'other-field-info' => 'La règle est respectée tant que l’autre champ est vide.',
            'codes'            => 'Codes autorisés',
            'codes-info'       => 'Un code par ligne ou séparés par des virgules. Les valeurs sont comparées exactement.',
            'after'            => 'Pas avant',
            'before'           => 'Pas après',
            'date-info'        => 'Une date telle que 2020-01-01 ou une date relative telle que today ou -18 years. Laissez l’un des deux vides pour une plage ouverte.',
            'messages'         => 'Message d’erreur',
            'messages-info'    => 'Affiché lorsqu’une valeur enfreint la règle, dans la langue de la personne ou du client API. Laissez vide pour le message par défaut. :attribute représente le nom du champ.',
            'sort-order'       => 'Ordre de tri',
            'save-btn'         => 'Enregistrer la règle de validation',
        ],

        'types' => [
            'regex'        => 'Correspond au motif',
            'min'          => 'Valeur minimale',
            'max'          => 'Valeur maximale',
            'length'       => 'Longueur',
            'cross_field'  => 'Comparé à un autre champ',
            'one_of_codes' => 'Un des codes listés',
            'date_range'   => 'Plage de dates',
        ],

        'severities' => [
            'block' => 'Bloquer',
            'warn'  => 'Avertir',
        ],

        'operators' => [
            'eq'  => 'égal à',
            'neq' => 'différent de',
            'gt'  => 'supérieur à',
            'gte' => 'au moins égal à',
            'lt'  => 'inférieur à',
            'lte' => 'au plus égal à',
        ],

        'messages' => [
            'default'      => ':attribute n’est pas valide.',
            'regex'        => ':attribute n’a pas le format attendu.',
            'min'          => ':attribute doit être au moins égal à :value.',
            'max'          => ':attribute doit être au plus égal à :value.',
            'length'       => ':attribute doit contenir entre :min et :max caractères.',
            'cross_field'  => ':attribute doit être :operator :field.',
            'one_of_codes' => ':attribute doit être l’un des codes suivants : :codes.',
            'date_range'   => ':attribute doit être une date comprise dans la plage autorisée.',
        ],

        'datagrid' => [
            'field'      => 'Champ',
            'type'       => 'Règle',
            'severity'   => 'Gravité',
            'sort-order' => 'Ordre de tri',
            'edit'       => 'Modifier',
            'delete'     => 'Supprimer',
        ],

        'validation' => [
            'too-many'     => 'Un type d’entité peut avoir au maximum :count règles de validation.',
            'invalid-date' => 'Saisissez une date ou une date relative telle que today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Standardisation',
        'info'           => 'Nettoyez les valeurs avant leur validation et leur enregistrement, depuis l’administration, les imports et l’API. Les transformations d’un champ s’exécutent selon leur ordre de tri.',
        'create-btn'     => 'Ajouter une transformation',
        'create-success' => 'Transformation créée avec succès.',
        'update-success' => 'Transformation mise à jour avec succès.',
        'delete-success' => 'Transformation supprimée avec succès.',

        'create' => [
            'title' => 'Ajouter une transformation',
        ],

        'edit' => [
            'title' => 'Modifier la transformation',
        ],

        'form' => [
            'field'            => 'Champ',
            'select-field'     => 'Sélectionner un champ',
            'transform'        => 'Transformation',
            'select-transform' => 'Sélectionner une transformation',
            'transform-info'   => 'S’applique uniquement aux valeurs textuelles ; les autres valeurs restent inchangées.',
            'sort-order'       => 'Ordre de tri',
            'sort-order-info'  => 'Les transformations d’un même champ s’exécutent de l’ordre de tri le plus bas au plus élevé.',
            'save-btn'         => 'Enregistrer la transformation',
        ],

        'types' => [
            'trim'            => 'Supprimer les espaces en début et fin',
            'lower'           => 'Minuscules',
            'upper'           => 'Majuscules',
            'title'           => 'Casse de titre',
            'collapse_spaces' => 'Réduire les espaces répétés',
            'email_normalize' => 'Normaliser l’adresse e-mail',
            'phone_e164'      => 'Normaliser le numéro de téléphone (E.164)',
            'strip_non_alnum' => 'Conserver uniquement lettres et chiffres',
        ],

        'datagrid' => [
            'field'      => 'Champ',
            'transform'  => 'Transformation',
            'sort-order' => 'Ordre de tri',
            'edit'       => 'Modifier',
            'delete'     => 'Supprimer',
        ],

        'validation' => [
            'taken'    => 'Ce champ exécute déjà cette transformation.',
            'too-many' => 'Un type d’entité peut avoir au maximum :count transformations.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Avertissement de validation',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Parcourir les :type en dessous',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Niveau supérieur',
        ],

        'datagrid' => [
            'erase'         => 'Effacer définitivement',
            'parent'        => 'Parent',
            'top-level'     => 'Niveau supérieur',
            'children'      => 'Enfants',
            'view-children' => 'Afficher les :count enregistrements situés sous celui-ci',
        ],

        'erase' => [
            'btn'             => 'Effacer définitivement',
            'confirm-title'   => 'Effacer définitivement cet enregistrement ?',
            'confirm-message' => 'Toutes les valeurs, le libellé et le code de cet enregistrement et des doublons fusionnés avec lui sont supprimés, ainsi que leur historique, leurs demandes de modification et leurs problèmes de qualité. L\'enregistrement est conservé comme espace réservé effacé afin que ses identifiants source et ses liens restent résolus. Cette action est irréversible.',
            'success'         => 'Enregistrement effacé définitivement.',
            'not-retired'     => 'Seul un enregistrement retiré peut être effacé. Retirez-le d\'abord.',
        ],

        'mass-retire' => [
            'success' => ':count enregistrement(s) retiré(s).',
            'pending' => ':count retrait(s) soumis pour approbation.',
            'skipped' => ':count enregistrement(s) ignoré(s) car ils ne sont ni brouillon ni actifs, ou ont été modifiés par quelqu\'un d\'autre.',
            'none'    => 'Aucun des enregistrements sélectionnés n\'a pu être retiré.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Approbation requise : demande de modification #:id',
            'description'           => ':requester a soumis une modification « :operation » sur :type à votre approbation.',
            'description-automated' => 'Une modification automatique « :operation » sur :type attend votre approbation.',
        ],
    ],

    'access' => [
        'menu'           => 'Accès aux données',
        'acl'            => [
            'index'  => 'Accès aux données',
            'create' => 'Créer',
            'edit'   => 'Modifier',
            'delete' => 'Supprimer',
        ],
        'principals' => [
            'role' => 'Rôle',
            'user' => 'Utilisateur',
        ],
        'actions' => [
            'view'    => 'Afficher',
            'edit'    => 'Modifier',
            'approve' => 'Approuver',
        ],
        'index' => [
            'title'      => 'Accès aux données',
            'create-btn' => 'Ajouter une autorisation',
            'info'       => 'Les autorisations déterminent à quels enregistrements des types d\'entité restreints chaque rôle ou utilisateur accède : l\'enregistrement choisi et tout ce qui se trouve en dessous. L\'accès issu du rôle d\'un utilisateur et celui des autorisations accordées à l\'utilisateur s\'additionnent.',
        ],
        'create' => [
            'title' => 'Ajouter une autorisation d\'accès aux données',
        ],
        'edit' => [
            'title' => 'Modifier l\'autorisation d\'accès aux données',
        ],
        'form' => [
            'save-btn'         => 'Enregistrer l\'autorisation',
            'principal'        => 'Accordée à',
            'principal-info'   => 'Une autorisation de rôle s\'applique à tous les utilisateurs ayant ce rôle. Accordez-la à un seul utilisateur pour une exception.',
            'principal-type'   => 'Accorder à',
            'role'             => 'Rôle',
            'user'             => 'Utilisateur',
            'scope'            => 'Périmètre',
            'scope-info'       => 'L\'autorisation couvre l\'enregistrement choisi et tous les enregistrements situés en dessous dans la hiérarchie.',
            'root'             => 'Racine du sous-arbre',
            'root-info'        => 'Par exemple un site ou une usine. Les enregistrements en dessous héritent de l\'autorisation.',
            'entity-type'      => 'Type d\'entité',
            'entity-type-info' => 'Limitez l\'autorisation à un type d\'entité restreint, ou laissez vide pour tous les types restreints.',
            'any-entity-type'  => 'Tous les types d\'entité restreints',
            'actions'          => 'Actions',
            'actions-info'     => 'Afficher lit les enregistrements ; Modifier change, retire, fusionne et crée des enregistrements sous la racine ; Approuver statue sur les modifications en attente. Modifier et Approuver incluent Afficher.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Type',
            'principal'         => 'Accordée à',
            'missing-principal' => 'Supprimé',
            'entity-type'       => 'Type d\'entité',
            'root'              => 'Racine du sous-arbre',
            'actions'           => 'Actions',
            'edit'              => 'Modifier',
            'delete'            => 'Supprimer',
        ],
        'role-card' => [
            'title'  => 'Accès aux données de référence',
            'info'   => 'À quels enregistrements des types d\'entité restreints les utilisateurs ayant ce rôle accèdent. Chaque autorisation couvre son enregistrement racine et tout ce qui se trouve en dessous.',
            'empty'  => 'Ce rôle n\'a pas encore d\'autorisation d\'accès aux données de référence.',
            'remove' => 'Retirer',
            'add'    => 'Ajouter une autorisation',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restreindre l\'accès par sous-arbre',
            'is-scoped-info' => 'Lorsque cette option est activée, les utilisateurs n\'accèdent qu\'aux enregistrements de ce type dans les sous-arbres accordés à leur rôle ou à eux-mêmes sur l\'écran Accès aux données. Laissez-la désactivée pour les données de référence partagées.',
        ],
        'validation' => [
            'record-not-found'    => 'Choisissez comme racine du sous-arbre un enregistrement que vous pouvez voir.',
            'parent-out-of-scope' => 'Choisissez un parent dans un sous-arbre où vous avez un accès en modification.',
        ],
        'create-success' => 'Autorisation d\'accès aux données ajoutée.',
        'update-success' => 'Autorisation d\'accès aux données mise à jour.',
        'delete-success' => 'Autorisation d\'accès aux données supprimée.',
    ],

    'install' => [
        'migrating' => 'Exécution des migrations des données de référence.',
        'demo'      => 'Installation des données de démonstration de fabrication.',
        'done'      => 'La gestion des données de référence est installée. Modules actifs : :modules.',
    ],
];
