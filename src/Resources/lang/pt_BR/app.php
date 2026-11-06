<?php

return [
    'menu' => [
        'master-data' => 'Dados mestres',
        'icons'       => 'Ícones',
    ],

    'acl' => [
        'master-data' => 'Dados mestres',
        'icons'       => [
            'index'  => 'Ícones',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Excluir',
        ],

        'records' => [
            'erase' => 'Apagar permanentemente',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dados mestres',
        'inside'     => 'Dentro de: :types',
        'top-level'  => 'Nível superior',
        'standalone' => 'Independente',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dados mestres',
            'records'     => 'Registros',
            'upsert'      => 'Inserir ou atualizar por ID do sistema de origem',
        ],

        'validation-failed'     => 'Falha na validação.',
        'change-held'           => 'A alteração está aguardando aprovação.',
        'xref-not-found'        => 'Nenhum registro corresponde a este ID externo.',
        'entity-type-not-found' => 'O tipo de entidade não foi encontrado.',
        'record-not-found'      => 'O registro não foi encontrado.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Tipo de entidade',
            'source-system'       => 'Sistema de origem',
            'code-list'           => 'Lista de códigos',
            'entity-type-info'    => 'O tipo de entidade cujos registros o arquivo contém.',
            'source-system-info'  => 'O sistema de onde o arquivo vem. Seus IDs externos são lidos da coluna external_id.',
            'code-list-info'      => 'A lista para a qual importar. Os códigos são gravados na versão de rascunho, que você publica separadamente.',
            'mapping-info'        => 'Uma linha por coluna renomeada: coluna do arquivo=código do campo, ou código do campo-idioma para um campo por idioma, por exemplo Asset Num=external_id e Description=name. Colunas sem linha são lidas pelo próprio nome.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Escolha um tipo de entidade ativo cujos registros você pode criar ou editar.',
            'source-system-invalid'              => 'Escolha um sistema de origem ativo.',
            'code-list-invalid'                  => 'Escolha uma lista de códigos existente que você pode editar.',
            'external-id-without-source-system'  => 'O arquivo tem uma coluna de ID externo (:column). Escolha o sistema de origem de onde ele vem ou remova a coluna.',
        ],

        'schedule' => [
            'job-not-found'  => 'Nenhum job de importação tem o código :code.',
            'not-masterdata' => 'O job :code não é uma importação de dados mestres.',
            'no-user'        => 'Informe o administrador com o qual a importação é executada usando --user ou MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Nenhum administrador ativo tem o e-mail :email.',
            'file-not-found' => 'O arquivo :path não foi encontrado no disco :disk.',
            'nothing'        => 'Nenhum arquivo aguardando em :path no disco :disk.',
            'queued'         => 'O arquivo :file foi colocado na fila para o job :code, acompanhado como #:id.',
            'imported'       => 'O arquivo :file foi importado para o job :code, acompanhado como #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Demonstração de manufatura pronta: :types tipos de entidade, :systems sistemas de origem, :records registros em uma árvore de unidades, :xrefs referências cruzadas, :lists listas de códigos, :fields campos, :aliases aliases.',
        'invalid-template' => 'Não foi possível ler o modelo :name ou ele tem uma versão de formato não compatível.',
    ],

    'platform-module' => [
        'name' => 'Dados mestres (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Tipos de entidade excluídos: :count.',
        'mass-delete-skipped' => 'Não excluídos porque ainda têm registros: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Tipos pai',
            'records-count'        => 'Registros',
            'fields-count'         => 'Campos',
            'updated-at'           => 'Atualizado',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Ordem de exibição',
        ],

        'edit' => [
            'schema-version' => 'Esquema v:version',
        ],

        'form' => [
            'basics'                    => 'Informações básicas',
            'identity'                  => 'Identidade',
            'hierarchy'                 => 'Hierarquia',
            'governance'                => 'Governança',
            'quality-matching'          => 'Qualidade e correspondência',
            'matching-info'             => 'As linhas recebidas dos sistemas de origem são associadas a registros existentes na caixa de correspondências.',
            'auto-match-threshold'      => 'Limite de correspondência automática',
            'auto-match-off'            => 'Desativado',
            'auto-match-threshold-info' => 'Deixe em branco para revisar cada linha. De 0,5 a 1: uma linha cuja melhor sugestão atinja pelo menos esse valor, sem outra sugestão tão alta, é aceita automaticamente. Tipos que exigem aprovação continuam retendo o vínculo até a aprovação.',
            'match-on-write'            => 'Verificar duplicatas ao salvar',
            'match-on-write-info'       => 'Cada registro salvo é comparado em segundo plano com os demais registros usando as regras de correspondência. Desative para encontrar duplicatas apenas nas análises de qualidade.',
            'manage-icons'              => 'Gerenciar ícones',
            'sort-order'                => 'Ordem de exibição',
            'sort-order-info'           => 'Números menores aparecem primeiro, de 0 a 9999; tipos com o mesmo número são ordenados pelo código.',
        ],

        'quick-create' => [
            'code-info'             => 'Letras minúsculas, dígitos e sublinhados, começando com uma letra. Não pode ser alterado depois de salvo.',
            'code-pattern-info'     => 'Use {seq} ou {seq:6} para o número sequencial e {TYPE} para o código do tipo, por exemplo {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Os registros chegam de vários sistemas de origem e são correspondidos e mesclados em um único registro mestre.',
                'centralized'   => 'Os registros são criados e mantidos aqui e depois publicados nos sistemas que os utilizam.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Regra de correspondência em :target',
        ],

        'show' => [
            'auto-matched' => 'Correspondência automática',
            'auto-pending' => 'Associado automaticamente; o vínculo aguarda aprovação.',
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
            'empty'        => 'Nenhum job de exportação publica registros deste tipo e status.',
            'job'          => 'Job de exportação',
            'format'       => 'Formato',
            'mode'         => 'Modo',
            'full'         => 'Completo',
            'delta'        => 'Somente alterações',
            'last-run-at'  => 'Última execução concluída',
            'no-run'       => 'Nunca executado',
            'record-state' => 'Este registro',
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
        'delete-success' => 'Ícone excluído com sucesso.',
        'delete-in-use'  => '{1} Este ícone é exibido por um tipo de entidade e não pode ser excluído.|[2,*] Este ícone é exibido por :count tipos de entidade e não pode ser excluído.',
        'index'          => [
            'title'      => 'Ícones',
            'create-btn' => 'Adicionar ícone',
        ],

        'create' => [
            'title'    => 'Adicionar ícone',
            'save-btn' => 'Salvar ícone',
        ],

        'edit' => [
            'title'    => 'Editar ícone',
            'save-btn' => 'Salvar ícone',
        ],

        'form' => [
            'code'      => 'Código',
            'name'      => 'Nome',
            'file'      => 'Arquivo do ícone',
            'file-info' => 'SVG, PNG ou WebP, até :size KB. Use uma única forma sobre fundo transparente: ela assume a cor do tema.',
            'preview'   => 'Pré-visualização',
            'usage'     => '{0} Nenhum tipo de entidade exibe este ícone ainda.|{1} Exibido por um tipo de entidade.|[2,*] Exibido por :count tipos de entidade.',
        ],

        'library' => [
            'barcode'    => 'Código de barras',
            'boxes'      => 'Materiais',
            'briefcase'  => 'Fornecedor de serviços',
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
            'user_round' => 'Contato',
            'users'      => 'Cliente',
            'warehouse'  => 'Armazém',
            'wrench'     => 'Equipamento',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Ícone',
            'code'    => 'Código',
            'name'    => 'Nome',
            'usage'   => 'Usado por',
            'edit'    => 'Editar',
            'delete'  => 'Excluir',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Correspondência',
        'title'          => 'Regras de correspondência',
        'info'           => 'Defina como os registros deste tipo são comparados para encontrar duplicatas e associar as linhas recebidas. Sem regras, o código e o rótulo são comparados.',
        'create-btn'     => 'Adicionar regra de correspondência',
        'create-success' => 'Regra de correspondência criada com sucesso.',
        'update-success' => 'Regra de correspondência atualizada com sucesso.',
        'delete-success' => 'Regra de correspondência excluída com sucesso.',

        'create' => [
            'title' => 'Adicionar regra de correspondência',
        ],

        'edit' => [
            'title' => 'Editar regra de correspondência',
        ],

        'form' => [
            'target'            => 'Comparar',
            'select-target'     => 'Selecione o que comparar',
            'target-info'       => 'O código do registro, seu rótulo ou um campo deste tipo de entidade.',
            'comparator'        => 'Comparador',
            'select-comparator' => 'Selecione um comparador',
            'comparator-info'   => 'Como os dois valores são comparados, por exemplo Aproximado para nomes com erros de digitação ou Numérico para valores monetários.',
            'weight'            => 'Peso',
            'weight-info'       => 'Quanto esta regra conta, de 0,1 a 100. A pontuação é a média ponderada das regras para as quais a linha recebida tem um valor.',
            'tolerance'         => 'Tolerância',
            'tolerance-info'    => 'A maior diferença entre dois números que ainda é considerada semelhante. Deixe em branco para corresponder apenas números iguais.',
            'days'              => 'Intervalo de datas (dias)',
            'days-info'         => 'O máximo de dias que duas datas podem estar separadas e ainda ser consideradas semelhantes. Deixe em branco para corresponder apenas o mesmo dia.',
            'sort-order'        => 'Ordem de exibição',
            'is-blocking'       => 'Chave de bloqueio',
            'is-blocking-info'  => 'Busca também os registros que compartilham exatamente este valor, mesmo quando o código e o rótulo são diferentes. Os campos precisam ser pesquisáveis.',
            'save-btn'          => 'Salvar regra de correspondência',
        ],

        'targets' => [
            'code'  => 'Código',
            'label' => 'Rótulo',
        ],

        'comparators' => [
            'exact'    => 'Exato',
            'fuzzy'    => 'Aproximado',
            'token'    => 'Mesmas palavras',
            'phonetic' => 'Som parecido',
            'numeric'  => 'Numérico',
            'date'     => 'Data',
        ],

        'datagrid' => [
            'target'     => 'Comparar',
            'comparator' => 'Comparador',
            'weight'     => 'Peso',
            'blocking'   => 'Chave de bloqueio',
            'sort-order' => 'Ordem de exibição',
            'yes'        => 'Sim',
            'no'         => 'Não',
            'edit'       => 'Editar',
            'delete'     => 'Excluir',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Somente o código, o rótulo ou um campo pesquisável pode ser uma chave de bloqueio.',
            'too-many'                => 'Um tipo de entidade pode ter no máximo :count regras de correspondência.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Prevalência',
        'title'          => 'Regras de prevalência',
        'info'           => 'Defina qual valor o registro mestre mantém quando dois registros deste tipo são mesclados. Sem regras, o registro sobrevivente mantém seu valor, a menos que esteja vazio.',
        'create-btn'     => 'Adicionar regra de prevalência',
        'create-success' => 'Regra de prevalência criada com sucesso.',
        'update-success' => 'Regra de prevalência atualizada com sucesso.',
        'delete-success' => 'Regra de prevalência excluída com sucesso.',

        'create' => [
            'title' => 'Adicionar regra de prevalência',
        ],

        'edit' => [
            'title' => 'Editar regra de prevalência',
        ],

        'form' => [
            'target'            => 'Aplica-se a',
            'select-target'     => 'Selecione a que a regra se aplica',
            'target-info'       => 'Um campo, o rótulo do registro ou todos os campos sem regra própria.',
            'strategy'          => 'Estratégia',
            'select-strategy'   => 'Selecione uma estratégia',
            'strategy-info'     => 'Como o valor sobrevivente é escolhido. Os responsáveis pelos dados ainda podem alterar cada escolha na tela de mesclagem.',
            'source-order'      => 'Ordem das origens',
            'select-sources'    => 'Selecione sistemas de origem',
            'source-order-info' => 'O sistema de origem mais confiável primeiro. Os sistemas omitidos seguem sua prioridade. Deixe vazio para usar apenas a prioridade dos sistemas de origem.',
            'sort-order'        => 'Ordem de exibição',
            'save-btn'          => 'Salvar regra de prevalência',
        ],

        'targets' => [
            'default' => 'Padrão para todos os campos',
            'label'   => 'Rótulo',
        ],

        'strategies' => [
            'source_priority' => 'Origem mais confiável',
            'most_recent'     => 'Atualizado mais recentemente',
            'most_complete'   => 'Mais completo',
            'keep_target'     => 'Manter o sobrevivente',
            'manual'          => 'O responsável pelos dados decide',
        ],

        'datagrid' => [
            'target'     => 'Aplica-se a',
            'strategy'   => 'Estratégia',
            'sort-order' => 'Ordem de exibição',
            'edit'       => 'Editar',
            'delete'     => 'Excluir',
        ],

        'validation' => [
            'target-taken' => 'Este tipo de entidade já tem uma regra de prevalência para este alvo.',
            'too-many'     => 'Um tipo de entidade pode ter no máximo :count regras de prevalência.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Navegar por :type abaixo',
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
            'view-children' => 'Mostrar os :count registros abaixo deste',
        ],

        'erase' => [
            'btn'             => 'Apagar permanentemente',
            'confirm-title'   => 'Apagar este registro permanentemente?',
            'confirm-message' => 'Todos os valores, o rótulo e o código deste registro e das duplicatas mescladas a ele serão removidos, junto com o histórico, as solicitações de alteração e os problemas de qualidade. O registro permanece como um marcador apagado para que seus IDs de origem e vínculos continuem funcionando. Isso não pode ser desfeito.',
            'success'         => 'Registro apagado permanentemente.',
            'not-retired'     => 'Somente um registro desativado pode ser apagado. Desative-o primeiro.',
        ],

        'mass-retire' => [
            'success' => ':count registro(s) desativado(s).',
            'pending' => ':count desativação(ões) enviada(s) para aprovação.',
            'skipped' => ':count registro(s) ignorado(s) porque não são rascunho nem ativos, ou foram alterados por outra pessoa.',
            'none'    => 'Nenhum dos registros selecionados pôde ser desativado.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Aprovação necessária: solicitação de alteração #:id',
            'description'           => ':requester enviou uma alteração ":operation" em :type para sua aprovação.',
            'description-automated' => 'Uma alteração automática ":operation" em :type está aguardando sua aprovação.',
        ],
    ],

    'access' => [
        'menu'           => 'Acesso a dados',
        'acl'            => [
            'index'  => 'Acesso a dados',
            'create' => 'Criar',
            'edit'   => 'Editar',
            'delete' => 'Excluir',
        ],
        'principals' => [
            'role' => 'Perfil',
            'user' => 'Usuário',
        ],
        'actions' => [
            'view'    => 'Visualizar',
            'edit'    => 'Editar',
            'approve' => 'Aprovar',
        ],
        'index' => [
            'title'      => 'Acesso a dados',
            'create-btn' => 'Adicionar permissão',
            'info'       => 'As permissões definem a quais registros de tipos de entidade com escopo cada perfil ou usuário tem acesso: o registro escolhido e tudo abaixo dele. O acesso vindo do perfil do usuário e das permissões concedidas ao usuário se somam.',
        ],
        'create' => [
            'title' => 'Adicionar permissão de acesso a dados',
        ],
        'edit' => [
            'title' => 'Editar permissão de acesso a dados',
        ],
        'form' => [
            'save-btn'         => 'Salvar permissão',
            'principal'        => 'Concedida a',
            'principal-info'   => 'Uma permissão de perfil alcança todos os usuários com esse perfil. Conceda a um único usuário para abrir uma exceção.',
            'principal-type'   => 'Conceder a',
            'role'             => 'Perfil',
            'user'             => 'Usuário',
            'scope'            => 'Escopo',
            'scope-info'       => 'A permissão cobre o registro escolhido e todos os registros abaixo dele na hierarquia.',
            'root'             => 'Raiz da subárvore',
            'root-info'        => 'Por exemplo, uma unidade ou uma fábrica. Os registros abaixo dela herdam a permissão.',
            'entity-type'      => 'Tipo de entidade',
            'entity-type-info' => 'Limite a permissão a um tipo de entidade com escopo ou deixe em branco para todos os tipos com escopo.',
            'any-entity-type'  => 'Todos os tipos de entidade com escopo',
            'actions'          => 'Ações',
            'actions-info'     => 'Visualizar lê registros; Editar altera, aposenta, mescla e cria registros abaixo da raiz; Aprovar decide sobre alterações retidas. Editar e Aprovar incluem Visualizar.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Tipo',
            'principal'         => 'Concedida a',
            'missing-principal' => 'Excluído',
            'entity-type'       => 'Tipo de entidade',
            'root'              => 'Raiz da subárvore',
            'actions'           => 'Ações',
            'edit'              => 'Editar',
            'delete'            => 'Excluir',
        ],
        'role-card' => [
            'title'  => 'Acesso a dados mestres',
            'info'   => 'A quais registros de tipos de entidade com escopo os usuários com este perfil têm acesso. Cada permissão cobre seu registro raiz e tudo abaixo dele.',
            'empty'  => 'Este perfil ainda não tem permissões de acesso a dados mestres.',
            'remove' => 'Remover',
            'add'    => 'Adicionar uma permissão',
        ],
        'entity-type' => [
            'is-scoped'      => 'Restringir acesso por subárvore',
            'is-scoped-info' => 'Quando ativado, os usuários só acessam registros deste tipo dentro das subárvores concedidas ao seu perfil ou a eles na tela Acesso a dados. Deixe desativado para dados de referência compartilhados.',
        ],
        'validation' => [
            'record-not-found'    => 'Escolha como raiz da subárvore um registro que você possa ver.',
            'parent-out-of-scope' => 'Escolha um pai dentro de uma subárvore em que você tenha acesso de edição.',
        ],
        'create-success' => 'Permissão de acesso a dados adicionada.',
        'update-success' => 'Permissão de acesso a dados atualizada.',
        'delete-success' => 'Permissão de acesso a dados excluída.',
    ],

    'install' => [
        'migrating' => 'Executando as migrações de dados mestres.',
        'demo'      => 'Instalando os dados de demonstração de manufatura.',
        'done'      => 'O gerenciamento de dados mestres está instalado. Módulos ativos: :modules.',
    ],
];
