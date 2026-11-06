<?php

return [
    'menu' => [
        'master-data' => 'Datos maestros',
        'icons'       => 'Iconos',
    ],

    'acl' => [
        'master-data' => 'Datos maestros',
        'icons'       => [
            'index'  => 'Iconos',
            'create' => 'Crear',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],

        'records' => [
            'erase' => 'Borrar definitivamente',
        ],
    ],

    'dashboard' => [
        'title'      => 'Datos maestros',
        'inside'     => 'Dentro de: :types',
        'top-level'  => 'Nivel superior',
        'standalone' => 'Independiente',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Datos maestros',
            'records'     => 'Registros',
            'upsert'      => 'Insertar o actualizar por ID del sistema de origen',
        ],

        'validation-failed'     => 'La validación falló.',
        'change-held'           => 'El cambio está pendiente de aprobación.',
        'xref-not-found'        => 'Ningún registro coincide con este identificador externo.',
        'entity-type-not-found' => 'No se encontró el tipo de entidad.',
        'record-not-found'      => 'No se encontró el registro.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipo de entidad',
            'source-system'       => 'Sistema de origen',
            'code-list'           => 'Lista de códigos',
            'entity-type-info'    => 'El tipo de entidad cuyos registros contiene el archivo.',
            'source-system-info'  => 'El sistema del que procede el archivo. Sus identificadores externos se leen de la columna external_id.',
            'code-list-info'      => 'La lista en la que se importa. Los códigos se escriben en su versión borrador, que publicas por separado.',
            'mapping-info'        => 'Una línea por columna renombrada: columna del archivo=código del campo, o código del campo-idioma para un campo por idioma, p. ej. Asset Num=external_id y Description=name. Las columnas sin línea se leen por su propio nombre.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Elige un tipo de entidad activo cuyos registros puedas crear o editar.',
            'source-system-invalid'              => 'Elige un sistema de origen activo.',
            'code-list-invalid'                  => 'Elige una lista de códigos existente que puedas editar.',
            'external-id-without-source-system'  => 'El archivo tiene una columna de identificador externo (:column). Elige el sistema de origen del que procede o elimina la columna.',
        ],

        'schedule' => [
            'job-not-found'  => 'Ningún trabajo de importación tiene el código :code.',
            'not-masterdata' => 'El trabajo :code no es una importación de datos maestros.',
            'no-user'        => 'Indique el administrador con el que se ejecuta la importación mediante --user o MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Ningún administrador activo tiene el correo electrónico :email.',
            'file-not-found' => 'No se encontró el archivo :path en el disco :disk.',
            'nothing'        => 'No hay ningún archivo pendiente en :path del disco :disk.',
            'queued'         => 'Se puso en cola :file para el trabajo :code, con seguimiento como #:id.',
            'imported'       => 'Se importó :file para el trabajo :code, con seguimiento como #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demo de manufactura lista: :types tipos de entidad, :systems sistemas de origen, :records registros en un árbol de plantas, :xrefs referencias cruzadas, :lists listas de códigos, :fields campos, :aliases alias.',
        'invalid-template' => 'No se pudo leer la plantilla :name o tiene una versión de formato no compatible.',
    ],

    'platform-module' => [
        'name' => 'Datos maestros (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipos de entidad eliminados: :count.',
        'mass-delete-skipped' => 'No se eliminaron porque todavía tienen registros: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipos padre',
            'records-count'        => 'Registros',
            'fields-count'         => 'Campos',
            'updated-at'           => 'Actualizado',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Orden',
        ],

        'edit' => [
            'schema-version' => 'Esquema v:version',
        ],

        'form' => [
            'basics'                    => 'Datos básicos',
            'identity'                  => 'Identidad',
            'hierarchy'                 => 'Jerarquía',
            'governance'                => 'Gobernanza',
            'quality-matching'          => 'Calidad y coincidencia',
            'matching-info'             => 'Las filas entrantes de los sistemas de origen se emparejan con registros existentes en la bandeja de coincidencias.',
            'auto-match-threshold'      => 'Umbral de coincidencia automática',
            'auto-match-off'            => 'Desactivado',
            'auto-match-threshold-info' => 'Déjalo vacío para revisar cada fila. De 0,5 a 1: una fila cuya mejor sugerencia alcance al menos este valor, sin otra sugerencia igual de alta, se acepta automáticamente. Los tipos que requieren aprobación siguen reteniendo el vínculo hasta su aprobación.',
            'match-on-write'            => 'Buscar duplicados al guardar',
            'match-on-write-info'       => 'Cada registro guardado se compara en segundo plano con los demás registros usando las reglas de coincidencia. Desactívalo para buscar duplicados solo en los análisis de calidad.',
            'manage-icons'              => 'Administrar iconos',
            'sort-order'                => 'Orden',
            'sort-order-info'           => 'Los números más bajos aparecen primero, de 0 a 9999; los tipos con el mismo número se ordenan por código.',
        ],

        'quick-create' => [
            'code-info'             => 'Letras minúsculas, dígitos y guiones bajos, comenzando con una letra. No se puede cambiar después de guardar.',
            'code-pattern-info'     => 'Use {seq} o {seq:6} para el número correlativo y {TYPE} para el código del tipo, por ejemplo {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Los registros llegan de varios sistemas de origen y se comparan y combinan en un único registro maestro.',
                'centralized'   => 'Los registros se crean y mantienen aquí y luego se publican en los sistemas que los usan.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regla de coincidencia sobre :target',
        ],

        'show' => [
            'auto-matched' => 'Coincidencia automática',
            'auto-pending' => 'Emparejado automáticamente; el vínculo espera aprobación.',
        ],

        'datagrid' => [
            'decision' => 'Decisión',
            'auto'     => 'Coincidencia automática',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Consumido por',
            'empty'        => 'Ningún trabajo de exportación publica registros de este tipo y estado.',
            'job'          => 'Trabajo de exportación',
            'format'       => 'Formato',
            'mode'         => 'Modo',
            'full'         => 'Completo',
            'delta'        => 'Solo cambios',
            'last-run-at'  => 'Última ejecución completada',
            'no-run'       => 'Nunca ejecutado',
            'record-state' => 'Este registro',
            'states'       => [
                'never_run'  => 'Aún no exportado',
                'pending'    => 'Cambiado desde la última ejecución',
                'included'   => 'Incluido en la última ejecución',
                'up_to_date' => 'Al día',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Icono creado correctamente.',
        'update-success' => 'Icono actualizado correctamente.',
        'delete-success' => 'Icono eliminado correctamente.',
        'delete-in-use'  => '{1} Este icono lo muestra un tipo de entidad y no se puede eliminar.|[2,*] Este icono lo muestran :count tipos de entidad y no se puede eliminar.',
        'index'          => [
            'title'      => 'Iconos',
            'create-btn' => 'Agregar icono',
        ],

        'create' => [
            'title'    => 'Agregar icono',
            'save-btn' => 'Guardar icono',
        ],

        'edit' => [
            'title'    => 'Editar icono',
            'save-btn' => 'Guardar icono',
        ],

        'form' => [
            'code'      => 'Código',
            'name'      => 'Nombre',
            'file'      => 'Archivo de icono',
            'file-info' => 'SVG, PNG o WebP, hasta :size KB. Usa una sola forma sobre fondo transparente: toma el color del tema.',
            'preview'   => 'Vista previa',
            'usage'     => '{0} Ningún tipo de entidad muestra todavía este icono.|{1} Mostrado por un tipo de entidad.|[2,*] Mostrado por :count tipos de entidad.',
        ],

        'library' => [
            'barcode'    => 'Código de barras',
            'boxes'      => 'Materiales',
            'briefcase'  => 'Proveedor',
            'building_2' => 'Empresa',
            'cpu'        => 'Activo',
            'factory'    => 'Planta',
            'file_text'  => 'Contrato',
            'globe'      => 'País',
            'handshake'  => 'Socio',
            'landmark'   => 'Entidad legal',
            'map_pin'    => 'Ubicación',
            'network'    => 'Jerarquía',
            'package'    => 'Producto',
            'store'      => 'Tienda',
            'tag'        => 'Categoría',
            'truck'      => 'Suplidor',
            'user_round' => 'Contacto',
            'users'      => 'Cliente',
            'warehouse'  => 'Almacén',
            'wrench'     => 'Equipo',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Icono',
            'code'    => 'Código',
            'name'    => 'Nombre',
            'usage'   => 'Usado por',
            'edit'    => 'Editar',
            'delete'  => 'Eliminar',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Coincidencia',
        'title'          => 'Reglas de coincidencia',
        'info'           => 'Decide cómo se comparan los registros de este tipo para encontrar duplicados y emparejar las filas entrantes. Sin reglas, se comparan el código y la etiqueta.',
        'create-btn'     => 'Agregar regla de coincidencia',
        'create-success' => 'Regla de coincidencia creada correctamente.',
        'update-success' => 'Regla de coincidencia actualizada correctamente.',
        'delete-success' => 'Regla de coincidencia eliminada correctamente.',

        'create' => [
            'title' => 'Agregar regla de coincidencia',
        ],

        'edit' => [
            'title' => 'Editar regla de coincidencia',
        ],

        'form' => [
            'target'            => 'Comparar',
            'select-target'     => 'Selecciona qué comparar',
            'target-info'       => 'El código del registro, su etiqueta o un campo de este tipo de entidad.',
            'comparator'        => 'Comparador',
            'select-comparator' => 'Selecciona un comparador',
            'comparator-info'   => 'Cómo se comparan los dos valores, p. ej. Aproximado para nombres con errores de tipeo o Numérico para montos.',
            'weight'            => 'Peso',
            'weight-info'       => 'Cuánto cuenta esta regla, de 0,1 a 100. La puntuación es el promedio ponderado de las reglas para las que la fila entrante tiene un valor.',
            'tolerance'         => 'Tolerancia',
            'tolerance-info'    => 'La mayor diferencia entre dos números que todavía se considera similar. Déjalo vacío para emparejar solo números iguales.',
            'days'              => 'Margen de fechas (días)',
            'days-info'         => 'El máximo de días que pueden separar dos fechas para que todavía se consideren similares. Déjalo vacío para emparejar solo el mismo día.',
            'sort-order'        => 'Orden',
            'is-blocking'       => 'Clave de bloqueo',
            'is-blocking-info'  => 'Busca también los registros que comparten exactamente este valor, aunque su código y su etiqueta sean distintos. Los campos deben ser buscables.',
            'save-btn'          => 'Guardar regla de coincidencia',
        ],

        'targets' => [
            'code'  => 'Código',
            'label' => 'Etiqueta',
        ],

        'comparators' => [
            'exact'    => 'Exacto',
            'fuzzy'    => 'Aproximado',
            'token'    => 'Mismas palabras',
            'phonetic' => 'Suena igual',
            'numeric'  => 'Numérico',
            'date'     => 'Fecha',
        ],

        'datagrid' => [
            'target'     => 'Comparar',
            'comparator' => 'Comparador',
            'weight'     => 'Peso',
            'blocking'   => 'Clave de bloqueo',
            'sort-order' => 'Orden',
            'yes'        => 'Sí',
            'no'         => 'No',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Solo el código, la etiqueta o un campo buscable pueden ser clave de bloqueo.',
            'too-many'                => 'Un tipo de entidad puede tener como máximo :count reglas de coincidencia.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalencia',
        'title'          => 'Reglas de prevalencia',
        'info'           => 'Decide qué valor conserva el registro maestro cuando se fusionan dos registros de este tipo. Sin reglas, el registro superviviente conserva su valor salvo que esté vacío.',
        'create-btn'     => 'Agregar regla de prevalencia',
        'create-success' => 'Regla de prevalencia creada con éxito.',
        'update-success' => 'Regla de prevalencia actualizada con éxito.',
        'delete-success' => 'Regla de prevalencia eliminada con éxito.',

        'create' => [
            'title' => 'Agregar regla de prevalencia',
        ],

        'edit' => [
            'title' => 'Editar regla de prevalencia',
        ],

        'form' => [
            'target'            => 'Se aplica a',
            'select-target'     => 'Selecciona a qué se aplica la regla',
            'target-info'       => 'Un campo, la etiqueta del registro o todos los campos sin regla propia.',
            'strategy'          => 'Estrategia',
            'select-strategy'   => 'Selecciona una estrategia',
            'strategy-info'     => 'Cómo se elige el valor superviviente. Los responsables de datos pueden cambiar cada elección en la pantalla de fusión.',
            'source-order'      => 'Orden de origen',
            'select-sources'    => 'Selecciona sistemas de origen',
            'source-order-info' => 'El sistema de origen más confiable primero. Los sistemas omitidos siguen su prioridad. Déjalo vacío para usar solo la prioridad de los sistemas de origen.',
            'sort-order'        => 'Orden',
            'save-btn'          => 'Guardar regla de prevalencia',
        ],

        'targets' => [
            'default' => 'Predeterminada para todos los campos',
            'label'   => 'Etiqueta',
        ],

        'strategies' => [
            'source_priority' => 'Origen más confiable',
            'most_recent'     => 'Actualizado más recientemente',
            'most_complete'   => 'Más completo',
            'keep_target'     => 'Conservar el superviviente',
            'manual'          => 'Decide el responsable de datos',
        ],

        'datagrid' => [
            'target'     => 'Se aplica a',
            'strategy'   => 'Estrategia',
            'sort-order' => 'Orden',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'target-taken' => 'Este tipo de entidad ya tiene una regla de prevalencia para este destino.',
            'too-many'     => 'Un tipo de entidad puede tener como máximo :count reglas de prevalencia.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validación',
        'title'          => 'Reglas de validación',
        'info'           => 'Compruebe los valores de los campos más allá de su tipo. Las reglas de bloqueo rechazan un guardado, una importación o una escritura por API; las reglas de advertencia lo permiten y generan una advertencia de calidad de datos.',
        'create-btn'     => 'Añadir regla de validación',
        'create-success' => 'Regla de validación creada correctamente.',
        'update-success' => 'Regla de validación actualizada correctamente.',
        'delete-success' => 'Regla de validación eliminada correctamente.',

        'create' => [
            'title' => 'Añadir regla de validación',
        ],

        'edit' => [
            'title' => 'Editar regla de validación',
        ],

        'form' => [
            'field'            => 'Campo',
            'select-field'     => 'Seleccione un campo',
            'type'             => 'Regla',
            'select-type'      => 'Seleccione una regla',
            'severity'         => 'Gravedad',
            'severity-info'    => '«Bloquear» rechaza los valores que incumplen la regla. «Advertir» los guarda y notifica una advertencia de calidad.',
            'pattern'          => 'Patrón',
            'pattern-info'     => 'Una expresión regular con delimitadores, como /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Valor',
            'min-length'       => 'Longitud mínima',
            'max-length'       => 'Longitud máxima',
            'operator'         => 'Comparación',
            'other-field'      => 'Comparado con',
            'other-field-info' => 'La regla se cumple mientras el otro campo esté vacío.',
            'codes'            => 'Códigos permitidos',
            'codes-info'       => 'Un código por línea o separados por comas. Los valores se comparan exactamente.',
            'after'            => 'No antes de',
            'before'           => 'No después de',
            'date-info'        => 'Una fecha como 2020-01-01 o una fecha relativa como today o -18 years. Deje uno vacío para un rango abierto.',
            'messages'         => 'Mensaje de error',
            'messages-info'    => 'Se muestra cuando un valor incumple la regla, en el idioma de la persona o del cliente de la API. Déjelo vacío para usar el mensaje predeterminado. :attribute representa el nombre del campo.',
            'sort-order'       => 'Orden de clasificación',
            'save-btn'         => 'Guardar regla de validación',
        ],

        'types' => [
            'regex'        => 'Coincide con el patrón',
            'min'          => 'Valor mínimo',
            'max'          => 'Valor máximo',
            'length'       => 'Longitud',
            'cross_field'  => 'Comparado con otro campo',
            'one_of_codes' => 'Uno de los códigos indicados',
            'date_range'   => 'Rango de fechas',
        ],

        'severities' => [
            'block' => 'Bloquear',
            'warn'  => 'Advertir',
        ],

        'operators' => [
            'eq'  => 'igual a',
            'neq' => 'distinto de',
            'gt'  => 'mayor que',
            'gte' => 'como mínimo',
            'lt'  => 'menor que',
            'lte' => 'como máximo',
        ],

        'messages' => [
            'default'      => ':attribute no es válido.',
            'regex'        => ':attribute no tiene el formato esperado.',
            'min'          => ':attribute debe ser como mínimo :value.',
            'max'          => ':attribute debe ser como máximo :value.',
            'length'       => ':attribute debe tener entre :min y :max caracteres.',
            'cross_field'  => ':attribute debe ser :operator :field.',
            'one_of_codes' => ':attribute debe ser uno de los siguientes: :codes.',
            'date_range'   => ':attribute debe ser una fecha dentro del rango permitido.',
            'unavailable'  => 'La validación :rule de :attribute ya no está disponible. Solicite a un administrador que elimine o reemplace la regla.',
        ],

        'datagrid' => [
            'field'      => 'Campo',
            'type'       => 'Regla',
            'severity'   => 'Gravedad',
            'sort-order' => 'Orden de clasificación',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'too-many'     => 'Un tipo de entidad puede tener como máximo :count reglas de validación.',
            'invalid-date' => 'Introduzca una fecha o una fecha relativa como today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Estandarización',
        'info'           => 'Limpie los valores antes de validarlos y guardarlos, tanto desde el administrador como en importaciones y en la API. Las transformaciones de un campo se ejecutan según su orden de clasificación.',
        'create-btn'     => 'Añadir transformación',
        'create-success' => 'Transformación creada correctamente.',
        'update-success' => 'Transformación actualizada correctamente.',
        'delete-success' => 'Transformación eliminada correctamente.',
        'unavailable'    => 'La transformación :transform de :attribute ya no está disponible. Solicite a un administrador que la elimine o la reemplace.',

        'create' => [
            'title' => 'Añadir transformación',
        ],

        'edit' => [
            'title' => 'Editar transformación',
        ],

        'form' => [
            'field'            => 'Campo',
            'select-field'     => 'Seleccione un campo',
            'transform'        => 'Transformación',
            'select-transform' => 'Seleccione una transformación',
            'transform-info'   => 'Se aplica solo a valores de texto; los demás valores se dejan como están.',
            'sort-order'       => 'Orden de clasificación',
            'sort-order-info'  => 'Las transformaciones del mismo campo se ejecutan desde el orden más bajo hacia el más alto.',
            'save-btn'         => 'Guardar transformación',
        ],

        'types' => [
            'trim'            => 'Eliminar espacios circundantes',
            'lower'           => 'Minúsculas',
            'upper'           => 'Mayúsculas',
            'title'           => 'Mayúscula inicial en cada palabra',
            'collapse_spaces' => 'Contraer espacios repetidos',
            'email_normalize' => 'Normalizar dirección de correo electrónico',
            'phone_e164'      => 'Normalizar número de teléfono (E.164)',
            'strip_non_alnum' => 'Conservar solo letras y dígitos',
        ],

        'datagrid' => [
            'field'      => 'Campo',
            'transform'  => 'Transformación',
            'sort-order' => 'Orden de clasificación',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'taken'    => 'Este campo ya ejecuta esta transformación.',
            'too-many' => 'Un tipo de entidad puede tener como máximo :count transformaciones.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Advertencia de validación',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Explorar :type inferiores',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Nivel superior',
        ],

        'datagrid' => [
            'erase'         => 'Borrar definitivamente',
            'parent'        => 'Padre',
            'top-level'     => 'Nivel superior',
            'children'      => 'Hijos',
            'view-children' => 'Mostrar los :count registros que dependen de este',
        ],

        'erase' => [
            'btn'             => 'Borrar definitivamente',
            'confirm-title'   => '¿Borrar este registro definitivamente?',
            'confirm-message' => 'Se eliminarán todos los valores, la etiqueta y el código de este registro y de los duplicados fusionados en él, junto con su historial, solicitudes de cambio e incidencias de calidad. El registro se conserva como marcador borrado para que sus ID de origen y enlaces sigan funcionando. Esta acción no se puede deshacer.',
            'success'         => 'Registro borrado definitivamente.',
            'not-retired'     => 'Solo se puede borrar un registro retirado. Retírelo primero.',
        ],

        'mass-retire' => [
            'success' => ':count registro(s) retirado(s).',
            'pending' => ':count retiro(s) enviado(s) para aprobación.',
            'skipped' => ':count registro(s) omitido(s) porque no son borradores ni activos, o alguien más los modificó.',
            'none'    => 'No se pudo retirar ninguno de los registros seleccionados.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Aprobación requerida: solicitud de cambio #:id',
            'description'           => ':requester envió un cambio ":operation" en :type para tu aprobación.',
            'description-automated' => 'Un cambio automático ":operation" en :type está esperando tu aprobación.',
        ],
    ],

    'access' => [
        'menu'           => 'Acceso a datos',
        'acl'            => [
            'index'  => 'Acceso a datos',
            'create' => 'Crear',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],
        'principals' => [
            'role' => 'Rol',
            'user' => 'Usuario',
        ],
        'actions' => [
            'view'    => 'Ver',
            'edit'    => 'Editar',
            'approve' => 'Aprobar',
        ],
        'index' => [
            'title'      => 'Acceso a datos',
            'create-btn' => 'Añadir permiso',
            'info'       => 'Los permisos deciden a qué registros de los tipos de entidad restringidos accede cada rol o usuario: el registro elegido y todo lo que hay debajo. El acceso del rol de un usuario y el de los permisos concedidos al usuario se suman.',
        ],
        'create' => [
            'title' => 'Añadir permiso de acceso a datos',
        ],
        'edit' => [
            'title' => 'Editar permiso de acceso a datos',
        ],
        'form' => [
            'save-btn'         => 'Guardar permiso',
            'principal'        => 'Concedido a',
            'principal-info'   => 'Un permiso de rol alcanza a todos los usuarios con ese rol. Concédelo a un solo usuario para una excepción.',
            'principal-type'   => 'Conceder a',
            'role'             => 'Rol',
            'user'             => 'Usuario',
            'scope'            => 'Ámbito',
            'scope-info'       => 'El permiso cubre el registro elegido y todos los registros que hay debajo en la jerarquía.',
            'root'             => 'Raíz del subárbol',
            'root-info'        => 'Por ejemplo, una sede o una planta. Los registros inferiores heredan el permiso.',
            'entity-type'      => 'Tipo de entidad',
            'entity-type-info' => 'Limita el permiso a un tipo de entidad restringido o déjalo vacío para todos los tipos restringidos.',
            'any-entity-type'  => 'Todos los tipos de entidad restringidos',
            'actions'          => 'Acciones',
            'actions-info'     => 'Ver lee registros; Editar modifica, retira, fusiona y crea registros bajo la raíz; Aprobar decide los cambios retenidos. Editar y Aprobar incluyen Ver.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipo',
            'principal'         => 'Concedido a',
            'missing-principal' => 'Eliminado',
            'entity-type'       => 'Tipo de entidad',
            'root'              => 'Raíz del subárbol',
            'actions'           => 'Acciones',
            'edit'              => 'Editar',
            'delete'            => 'Eliminar',
        ],
        'role-card' => [
            'title'  => 'Acceso a datos maestros',
            'info'   => 'A qué registros de los tipos de entidad restringidos acceden los usuarios con este rol. Cada permiso cubre su registro raíz y todo lo que hay debajo.',
            'empty'  => 'Este rol aún no tiene permisos de acceso a datos maestros.',
            'remove' => 'Quitar',
            'add'    => 'Añadir un permiso',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restringir el acceso por subárbol',
            'is-scoped-info' => 'Si está activado, los usuarios solo acceden a los registros de este tipo dentro de los subárboles concedidos a su rol o a ellos en la pantalla Acceso a datos. Déjalo desactivado para datos de referencia compartidos.',
        ],
        'validation' => [
            'record-not-found'    => 'Elige como raíz del subárbol un registro que puedas ver.',
            'parent-out-of-scope' => 'Elige un elemento padre dentro de un subárbol donde tengas acceso de edición.',
        ],
        'create-success' => 'Permiso de acceso a datos añadido.',
        'update-success' => 'Permiso de acceso a datos actualizado.',
        'delete-success' => 'Permiso de acceso a datos eliminado.',
    ],

    'install' => [
        'migrating' => 'Ejecutando las migraciones de datos maestros.',
        'demo'      => 'Instalando los datos de demostración de manufactura.',
        'done'      => 'La gestión de datos maestros está instalada. Módulos activos: :modules.',
    ],
];
