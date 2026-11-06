<?php

return [
    'menu' => [
        'master-data' => 'Dados mestre',
        'icons'       => 'Ícones',
    ],

    'acl' => [
        'master-data' => 'Dados mestre',
        'icons'       => [
            'index'  => 'Ícones',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],

        'records' => [
            'erase' => 'Apagar permanentemente',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dados mestre',
        'inside'     => 'Dentro de: :types',
        'top-level'  => 'Nível superior',
        'standalone' => 'Independente',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dados mestres',
            'records'     => 'Registos',
            'upsert'      => 'Inserir ou atualizar por ID do sistema de origem',
        ],

        'validation-failed'     => 'A validação falhou.',
        'change-held'           => 'A alteração está a aguardar aprovação.',
        'xref-not-found'        => 'Nenhum registo corresponde a este ID externo.',
        'entity-type-not-found' => 'O tipo de entidade não foi encontrado.',
        'record-not-found'      => 'O registo não foi encontrado.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipo de entidade',
            'source-system'       => 'Sistema de origem',
            'code-list'           => 'Lista de códigos',
            'entity-type-info'    => 'O tipo de entidade cujos registos o ficheiro contém.',
            'source-system-info'  => 'O sistema de onde provém o ficheiro. Os seus IDs externos são lidos da coluna external_id.',
            'code-list-info'      => 'A lista para onde importar. Os códigos são escritos na sua versão de rascunho, que publica separadamente.',
            'mapping-info'        => 'Uma linha por coluna renomeada: coluna do ficheiro=código do campo, ou código do campo-idioma para um campo por idioma, por exemplo Asset Num=external_id e Description=name. As colunas sem linha são lidas pelo próprio nome.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Escolha um tipo de entidade ativo cujos registos pode criar ou editar.',
            'source-system-invalid'              => 'Escolha um sistema de origem ativo.',
            'code-list-invalid'                  => 'Escolha uma lista de códigos existente que pode editar.',
            'external-id-without-source-system'  => 'O ficheiro tem uma coluna de ID externo (:column). Escolha o sistema de origem de onde provém ou remova a coluna.',
        ],

        'schedule' => [
            'job-not-found'  => 'Nenhuma tarefa de importação tem o código :code.',
            'not-masterdata' => 'A tarefa :code não é uma importação de dados mestre.',
            'no-user'        => 'Indique o administrador com o qual a importação é executada através de --user ou MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Nenhum administrador ativo tem o e-mail :email.',
            'file-not-found' => 'O ficheiro :path não foi encontrado no disco :disk.',
            'nothing'        => 'Nenhum ficheiro está a aguardar em :path no disco :disk.',
            'queued'         => 'O ficheiro :file foi colocado em fila para a tarefa :code, acompanhado como #:id.',
            'imported'       => 'O ficheiro :file foi importado para a tarefa :code, acompanhado como #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demonstração de fabrico pronta: :types tipos de entidade, :systems sistemas de origem, :records registos numa árvore de instalações, :xrefs referências cruzadas, :lists listas de códigos, :fields campos, :aliases aliases.',
        'invalid-template' => 'Não foi possível ler o modelo :name ou este tem uma versão de formato não suportada.',
    ],

    'platform-module' => [
        'name' => 'Dados mestre (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipos de entidade eliminados: :count.',
        'mass-delete-skipped' => 'Não eliminados porque ainda têm registos: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipos pai',
            'records-count'        => 'Registos',
            'fields-count'         => 'Campos',
            'updated-at'           => 'Atualizado',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordem de apresentação',
        ],

        'edit' => [
            'schema-version' => 'Esquema v:version',
        ],

        'form' => [
            'basics'                    => 'Informações básicas',
            'identity'                  => 'Identidade',
            'hierarchy'                 => 'Hierarquia',
            'governance'                => 'Governação',
            'quality-matching'          => 'Qualidade e correspondência',
            'matching-info'             => 'As linhas recebidas dos sistemas de origem são associadas a registos existentes na caixa de correspondências.',
            'auto-match-threshold'      => 'Limiar de correspondência automática',
            'auto-match-off'            => 'Desativado',
            'auto-match-threshold-info' => 'Deixe em branco para rever cada linha. De 0,5 a 1: uma linha cuja melhor sugestão atinja pelo menos este valor, sem outra sugestão igualmente alta, é aceite automaticamente. Os tipos que exigem aprovação continuam a reter a ligação até à aprovação.',
            'match-on-write'            => 'Procurar duplicados ao guardar',
            'match-on-write-info'       => 'Cada registo guardado é comparado em segundo plano com os restantes registos através das regras de correspondência. Desative para encontrar duplicados apenas nas análises de qualidade.',
            'manage-icons'              => 'Gerir ícones',
            'sort-order'                => 'Ordem de apresentação',
            'sort-order-info'           => 'Os números mais baixos aparecem primeiro, de 0 a 9999; os tipos com o mesmo número são ordenados pelo código.',
        ],

        'quick-create' => [
            'code-info'             => 'Letras minúsculas, dígitos e sublinhados, a começar por uma letra. Não pode ser alterado depois de guardado.',
            'code-pattern-info'     => 'Utilize {seq} ou {seq:6} para o número sequencial e {TYPE} para o código do tipo, por exemplo {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Os registos chegam de vários sistemas de origem e são correspondidos e fundidos num único registo mestre.',
                'centralized'   => 'Os registos são criados e mantidos aqui e depois publicados nos sistemas que os utilizam.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regra de correspondência em :target',
        ],

        'show' => [
            'auto-matched' => 'Correspondência automática',
            'auto-pending' => 'Associado automaticamente; a ligação aguarda aprovação.',
        ],

        'datagrid' => [
            'decision' => 'Decisão',
            'auto'     => 'Correspondência automática',
            'manual'   => 'Manual',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Consumido por',
            'empty'        => 'Nenhuma tarefa de exportação publica registos deste tipo e estado.',
            'job'          => 'Tarefa de exportação',
            'format'       => 'Formato',
            'mode'         => 'Modo',
            'full'         => 'Completo',
            'delta'        => 'Apenas alterações',
            'last-run-at'  => 'Última execução concluída',
            'no-run'       => 'Nunca executada',
            'record-state' => 'Este registo',
            'states'       => [
                'never_run'  => 'Ainda não exportado',
                'pending'    => 'Alterado desde a última execução',
                'included'   => 'Incluído na última execução',
                'up_to_date' => 'Atualizado',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Ícone criado com sucesso.',
        'update-success' => 'Ícone atualizado com sucesso.',
        'delete-success' => 'Ícone eliminado com sucesso.',
        'delete-in-use'  => '{1} Este ícone é apresentado por um tipo de entidade e não pode ser eliminado.|[2,*] Este ícone é apresentado por :count tipos de entidade e não pode ser eliminado.',
        'index'          => [
            'title'      => 'Ícones',
            'create-btn' => 'Adicionar ícone',
        ],

        'create' => [
            'title'    => 'Adicionar ícone',
            'save-btn' => 'Guardar ícone',
        ],

        'edit' => [
            'title'    => 'Editar ícone',
            'save-btn' => 'Guardar ícone',
        ],

        'form' => [
            'code'      => 'Código',
            'name'      => 'Nome',
            'file'      => 'Ficheiro do ícone',
            'file-info' => 'SVG, PNG ou WebP, até :size KB. Utilize uma única forma sobre fundo transparente: assume a cor do tema.',
            'preview'   => 'Pré-visualização',
            'usage'     => '{0} Nenhum tipo de entidade apresenta ainda este ícone.|{1} Apresentado por um tipo de entidade.|[2,*] Apresentado por :count tipos de entidade.',
        ],

        'library' => [
            'barcode'    => 'Código de barras',
            'boxes'      => 'Materiais',
            'briefcase'  => 'Prestador de serviços',
            'building_2' => 'Empresa',
            'cpu'        => 'Ativo',
            'factory'    => 'Fábrica',
            'file_text'  => 'Contrato',
            'globe'      => 'País',
            'handshake'  => 'Parceiro',
            'landmark'   => 'Entidade jurídica',
            'map_pin'    => 'Localização',
            'network'    => 'Hierarquia',
            'package'    => 'Produto',
            'store'      => 'Loja',
            'tag'        => 'Categoria',
            'truck'      => 'Fornecedor',
            'user_round' => 'Contacto',
            'users'      => 'Cliente',
            'warehouse'  => 'Armazém',
            'wrench'     => 'Equipamento',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ícone',
            'code'    => 'Código',
            'name'    => 'Nome',
            'usage'   => 'Utilizado por',
            'edit'    => 'Editar',
            'delete'  => 'Eliminar',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Correspondência',
        'title'          => 'Regras de correspondência',
        'info'           => 'Defina como os registos deste tipo são comparados para encontrar duplicados e associar as linhas recebidas. Sem regras, são comparados o código e a etiqueta.',
        'create-btn'     => 'Adicionar regra de correspondência',
        'create-success' => 'Regra de correspondência criada com sucesso.',
        'update-success' => 'Regra de correspondência atualizada com sucesso.',
        'delete-success' => 'Regra de correspondência eliminada com sucesso.',

        'create' => [
            'title' => 'Adicionar regra de correspondência',
        ],

        'edit' => [
            'title' => 'Editar regra de correspondência',
        ],

        'form' => [
            'target'            => 'Comparar',
            'select-target'     => 'Selecione o que comparar',
            'target-info'       => 'O código do registo, a respetiva etiqueta ou um campo deste tipo de entidade.',
            'comparator'        => 'Comparador',
            'select-comparator' => 'Selecione um comparador',
            'comparator-info'   => 'Como os dois valores são comparados, por exemplo Aproximado para nomes com erros ortográficos ou Numérico para montantes.',
            'weight'            => 'Peso',
            'weight-info'       => 'Quanto conta esta regra, de 0,1 a 100. A pontuação é a média ponderada das regras para as quais a linha recebida tem um valor.',
            'tolerance'         => 'Tolerância',
            'tolerance-info'    => 'A maior diferença entre dois números que ainda é considerada semelhante. Deixe em branco para fazer corresponder apenas números iguais.',
            'days'              => 'Intervalo de datas (dias)',
            'days-info'         => 'O número máximo de dias que podem separar duas datas para que ainda sejam consideradas semelhantes. Deixe em branco para fazer corresponder apenas o mesmo dia.',
            'sort-order'        => 'Ordem de apresentação',
            'is-blocking'       => 'Chave de bloqueio',
            'is-blocking-info'  => 'Procura também os registos que partilham exatamente este valor, mesmo quando o código e a etiqueta são diferentes. Os campos têm de ser pesquisáveis.',
            'save-btn'          => 'Guardar regra de correspondência',
        ],

        'targets' => [
            'code'  => 'Código',
            'label' => 'Etiqueta',
        ],

        'comparators' => [
            'exact'    => 'Exato',
            'fuzzy'    => 'Aproximado',
            'token'    => 'Mesmas palavras',
            'phonetic' => 'Som semelhante',
            'numeric'  => 'Numérico',
            'date'     => 'Data',
        ],

        'datagrid' => [
            'target'     => 'Comparar',
            'comparator' => 'Comparador',
            'weight'     => 'Peso',
            'blocking'   => 'Chave de bloqueio',
            'sort-order' => 'Ordem de apresentação',
            'yes'        => 'Sim',
            'no'         => 'Não',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Apenas o código, a etiqueta ou um campo pesquisável podem ser uma chave de bloqueio.',
            'too-many'                => 'Um tipo de entidade pode ter, no máximo, :count regras de correspondência.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalência',
        'title'          => 'Regras de prevalência',
        'info'           => 'Defina que valor o registo mestre mantém quando dois registos deste tipo são fundidos. Sem regras, o registo sobrevivente mantém o seu valor, a menos que esteja vazio.',
        'create-btn'     => 'Adicionar regra de prevalência',
        'create-success' => 'Regra de prevalência criada com sucesso.',
        'update-success' => 'Regra de prevalência atualizada com sucesso.',
        'delete-success' => 'Regra de prevalência eliminada com sucesso.',

        'create' => [
            'title' => 'Adicionar regra de prevalência',
        ],

        'edit' => [
            'title' => 'Editar regra de prevalência',
        ],

        'form' => [
            'target'            => 'Aplica-se a',
            'select-target'     => 'Selecione a que a regra se aplica',
            'target-info'       => 'Um campo, a etiqueta do registo ou todos os campos sem regra própria.',
            'strategy'          => 'Estratégia',
            'select-strategy'   => 'Selecione uma estratégia',
            'strategy-info'     => 'Como o valor sobrevivente é escolhido. Os responsáveis pelos dados podem sempre alterar cada escolha no ecrã de fusão.',
            'source-order'      => 'Ordem das origens',
            'select-sources'    => 'Selecione sistemas de origem',
            'source-order-info' => 'O sistema de origem mais fiável primeiro. Os sistemas omitidos seguem a sua prioridade. Deixe vazio para usar apenas a prioridade dos sistemas de origem.',
            'sort-order'        => 'Ordem de apresentação',
            'save-btn'          => 'Guardar regra de prevalência',
        ],

        'targets' => [
            'default' => 'Predefinição para todos os campos',
            'label'   => 'Etiqueta',
        ],

        'strategies' => [
            'source_priority' => 'Origem mais fiável',
            'most_recent'     => 'Atualizado mais recentemente',
            'most_complete'   => 'Mais completo',
            'keep_target'     => 'Manter o sobrevivente',
            'manual'          => 'O responsável pelos dados decide',
        ],

        'datagrid' => [
            'target'     => 'Aplica-se a',
            'strategy'   => 'Estratégia',
            'sort-order' => 'Ordem de apresentação',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'target-taken' => 'Este tipo de entidade já tem uma regra de prevalência para este alvo.',
            'too-many'     => 'Um tipo de entidade pode ter, no máximo, :count regras de prevalência.',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'Validação',
        'title'          => 'Regras de validação',
        'info'           => 'Verifique os valores dos campos para além do tipo. As regras de bloqueio recusam uma gravação, importação ou escrita via API; as regras de aviso permitem-na e geram um aviso de qualidade dos dados.',
        'create-btn'     => 'Adicionar regra de validação',
        'create-success' => 'Regra de validação criada com sucesso.',
        'update-success' => 'Regra de validação atualizada com sucesso.',
        'delete-success' => 'Regra de validação eliminada com sucesso.',

        'create' => [
            'title' => 'Adicionar regra de validação',
        ],

        'edit' => [
            'title' => 'Editar regra de validação',
        ],

        'form' => [
            'field'            => 'Campo',
            'select-field'     => 'Selecione um campo',
            'type'             => 'Regra',
            'select-type'      => 'Selecione uma regra',
            'severity'         => 'Gravidade',
            'severity-info'    => '«Bloquear» recusa valores que violam a regra. «Avisar» guarda-os e regista um aviso de qualidade.',
            'pattern'          => 'Padrão',
            'pattern-info'     => 'Uma expressão regular com delimitadores, como /^[A-Z]{2}[0-9]+$/.',
            'value'            => 'Valor',
            'min-length'       => 'Comprimento mínimo',
            'max-length'       => 'Comprimento máximo',
            'operator'         => 'Comparação',
            'other-field'      => 'Comparado com',
            'other-field-info' => 'A regra é cumprida enquanto o outro campo estiver vazio.',
            'codes'            => 'Códigos permitidos',
            'codes-info'       => 'Um código por linha ou separados por vírgulas. Os valores são comparados exatamente.',
            'after'            => 'Não antes de',
            'before'           => 'Não depois de',
            'date-info'        => 'Uma data como 2020-01-01 ou uma data relativa como today ou -18 years. Deixe um deles vazio para um intervalo aberto.',
            'messages'         => 'Mensagem de erro',
            'messages-info'    => 'Apresentada quando um valor viola a regra, no idioma da pessoa ou do cliente da API. Deixe vazio para a mensagem predefinida. :attribute representa o nome do campo.',
            'sort-order'       => 'Ordem de classificação',
            'save-btn'         => 'Guardar regra de validação',
        ],

        'types' => [
            'regex'        => 'Corresponde ao padrão',
            'min'          => 'Valor mínimo',
            'max'          => 'Valor máximo',
            'length'       => 'Comprimento',
            'cross_field'  => 'Comparado com outro campo',
            'one_of_codes' => 'Um dos códigos indicados',
            'date_range'   => 'Intervalo de datas',
        ],

        'severities' => [
            'block' => 'Bloquear',
            'warn'  => 'Avisar',
        ],

        'operators' => [
            'eq'  => 'igual a',
            'neq' => 'diferente de',
            'gt'  => 'maior que',
            'gte' => 'no mínimo',
            'lt'  => 'menor que',
            'lte' => 'no máximo',
        ],

        'messages' => [
            'default'      => ':attribute não é válido.',
            'regex'        => ':attribute não tem o formato esperado.',
            'min'          => ':attribute deve ser no mínimo :value.',
            'max'          => ':attribute deve ser no máximo :value.',
            'length'       => ':attribute deve ter entre :min e :max carateres.',
            'cross_field'  => ':attribute deve ser :operator :field.',
            'one_of_codes' => ':attribute deve ser um dos seguintes: :codes.',
            'date_range'   => ':attribute deve ser uma data dentro do intervalo permitido.',
        ],

        'datagrid' => [
            'field'      => 'Campo',
            'type'       => 'Regra',
            'severity'   => 'Gravidade',
            'sort-order' => 'Ordem de classificação',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'too-many'     => 'Um tipo de entidade pode ter no máximo :count regras de validação.',
            'invalid-date' => 'Introduza uma data ou uma data relativa como today.',
        ],
    ],

    'field-transforms' => [
        'title'          => 'Padronização',
        'info'           => 'Limpe os valores antes de serem validados e guardados, tanto na administração como em importações e na API. As transformações de um campo são executadas pela ordem de classificação.',
        'create-btn'     => 'Adicionar transformação',
        'create-success' => 'Transformação criada com sucesso.',
        'update-success' => 'Transformação atualizada com sucesso.',
        'delete-success' => 'Transformação eliminada com sucesso.',

        'create' => [
            'title' => 'Adicionar transformação',
        ],

        'edit' => [
            'title' => 'Editar transformação',
        ],

        'form' => [
            'field'            => 'Campo',
            'select-field'     => 'Selecione um campo',
            'transform'        => 'Transformação',
            'select-transform' => 'Selecione uma transformação',
            'transform-info'   => 'Aplica-se apenas a valores de texto; os restantes valores ficam como estão.',
            'sort-order'       => 'Ordem de classificação',
            'sort-order-info'  => 'As transformações do mesmo campo são executadas da menor ordem para a maior.',
            'save-btn'         => 'Guardar transformação',
        ],

        'types' => [
            'trim'            => 'Remover espaços nas extremidades',
            'lower'           => 'Minúsculas',
            'upper'           => 'Maiúsculas',
            'title'           => 'Iniciais maiúsculas',
            'collapse_spaces' => 'Reduzir espaços repetidos',
            'email_normalize' => 'Normalizar endereço de e-mail',
            'phone_e164'      => 'Normalizar número de telefone (E.164)',
            'strip_non_alnum' => 'Manter apenas letras e dígitos',
        ],

        'datagrid' => [
            'field'      => 'Campo',
            'transform'  => 'Transformação',
            'sort-order' => 'Ordem de classificação',
            'edit'       => 'Editar',
            'delete'     => 'Eliminar',
        ],

        'validation' => [
            'taken'    => 'Este campo já executa esta transformação.',
            'too-many' => 'Um tipo de entidade pode ter no máximo :count transformações.',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'Aviso de validação',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Explorar :type abaixo',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Nível superior',
        ],

        'datagrid' => [
            'erase'         => 'Apagar permanentemente',
            'parent'        => 'Pai',
            'top-level'     => 'Nível superior',
            'children'      => 'Filhos',
            'view-children' => 'Mostrar os :count registos abaixo deste',
        ],

        'erase' => [
            'btn'             => 'Apagar permanentemente',
            'confirm-title'   => 'Apagar este registo permanentemente?',
            'confirm-message' => 'Todos os valores, a etiqueta e o código deste registo e dos duplicados fundidos nele são removidos, juntamente com o histórico, os pedidos de alteração e os problemas de qualidade. O registo mantém-se como marcador apagado para que os IDs de origem e as ligações continuem a funcionar. Esta ação não pode ser anulada.',
            'success'         => 'Registo apagado permanentemente.',
            'not-retired'     => 'Só é possível apagar um registo retirado. Retire-o primeiro.',
        ],

        'mass-retire' => [
            'success' => ':count registo(s) retirado(s).',
            'pending' => ':count retirada(s) enviada(s) para aprovação.',
            'skipped' => ':count registo(s) ignorado(s) por não serem rascunho nem ativos, ou por terem sido alterados por outra pessoa.',
            'none'    => 'Não foi possível retirar nenhum dos registos selecionados.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Aprovação necessária: pedido de alteração #:id',
            'description'           => ':requester submeteu uma alteração ":operation" em :type para a sua aprovação.',
            'description-automated' => 'Uma alteração automática ":operation" em :type aguarda a sua aprovação.',
        ],
    ],

    'access' => [
        'menu'           => 'Acesso a dados',
        'acl'            => [
            'index'  => 'Acesso a dados',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Eliminar',
        ],
        'principals' => [
            'role' => 'Perfil',
            'user' => 'Utilizador',
        ],
        'actions' => [
            'view'    => 'Ver',
            'edit'    => 'Editar',
            'approve' => 'Aprovar',
        ],
        'index' => [
            'title'      => 'Acesso a dados',
            'create-btn' => 'Adicionar permissão',
            'info'       => 'As permissões definem a que registos de tipos de entidade com âmbito cada perfil ou utilizador tem acesso: o registo escolhido e tudo o que está abaixo. O acesso proveniente do perfil de um utilizador e das permissões concedidas ao utilizador somam-se.',
        ],
        'create' => [
            'title' => 'Adicionar permissão de acesso a dados',
        ],
        'edit' => [
            'title' => 'Editar permissão de acesso a dados',
        ],
        'form' => [
            'save-btn'         => 'Guardar permissão',
            'principal'        => 'Concedida a',
            'principal-info'   => 'Uma permissão de perfil abrange todos os utilizadores com esse perfil. Conceda a um único utilizador para abrir uma exceção.',
            'principal-type'   => 'Conceder a',
            'role'             => 'Perfil',
            'user'             => 'Utilizador',
            'scope'            => 'Âmbito',
            'scope-info'       => 'A permissão abrange o registo escolhido e todos os registos abaixo dele na hierarquia.',
            'root'             => 'Raiz da subárvore',
            'root-info'        => 'Por exemplo, um local ou uma fábrica. Os registos abaixo herdam a permissão.',
            'entity-type'      => 'Tipo de entidade',
            'entity-type-info' => 'Limite a permissão a um tipo de entidade com âmbito ou deixe vazio para todos os tipos com âmbito.',
            'any-entity-type'  => 'Todos os tipos de entidade com âmbito',
            'actions'          => 'Ações',
            'actions-info'     => 'Ver lê registos; Editar altera, retira, funde e cria registos abaixo da raiz; Aprovar decide sobre alterações retidas. Editar e Aprovar incluem Ver.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipo',
            'principal'         => 'Concedida a',
            'missing-principal' => 'Eliminado',
            'entity-type'       => 'Tipo de entidade',
            'root'              => 'Raiz da subárvore',
            'actions'           => 'Ações',
            'edit'              => 'Editar',
            'delete'            => 'Eliminar',
        ],
        'role-card' => [
            'title'  => 'Acesso a dados mestre',
            'info'   => 'A que registos de tipos de entidade com âmbito os utilizadores com este perfil têm acesso. Cada permissão abrange o seu registo raiz e tudo o que está abaixo.',
            'empty'  => 'Este perfil ainda não tem permissões de acesso a dados mestre.',
            'remove' => 'Remover',
            'add'    => 'Adicionar uma permissão',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restringir o acesso por subárvore',
            'is-scoped-info' => 'Quando ativado, os utilizadores só acedem a registos deste tipo dentro das subárvores concedidas ao seu perfil ou a eles no ecrã Acesso a dados. Deixe desativado para dados de referência partilhados.',
        ],
        'validation' => [
            'record-not-found'    => 'Escolha como raiz da subárvore um registo que possa ver.',
            'parent-out-of-scope' => 'Escolha um elemento superior dentro de uma subárvore em que tenha acesso de edição.',
        ],
        'create-success' => 'Permissão de acesso a dados adicionada.',
        'update-success' => 'Permissão de acesso a dados atualizada.',
        'delete-success' => 'Permissão de acesso a dados eliminada.',
    ],

    'install' => [
        'migrating' => 'A executar as migrações de dados mestres.',
        'demo'      => 'A instalar os dados de demonstração de fabrico.',
        'done'      => 'A gestão de dados mestres está instalada. Módulos ativos: :modules.',
    ],
];
