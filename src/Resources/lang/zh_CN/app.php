<?php

return [
    'menu' => [
        'master-data' => '主数据',
        'icons'       => '图标',
    ],

    'acl' => [
        'master-data' => '主数据',
        'icons'       => [
            'index'  => '图标',
            'create' => '创建',
            'edit'   => '编辑',
            'delete' => '删除',
        ],

        'records' => [
            'erase' => '永久清除',
        ],
    ],

    'dashboard' => [
        'title'      => '主数据',
        'inside'     => '隶属于：:types',
        'top-level'  => '顶层',
        'standalone' => '独立',
    ],

    'api' => [
        'acl' => [
            'master-data' => '主数据',
            'records'     => '记录',
            'upsert'      => '按源系统 ID 插入或更新',
        ],

        'validation-failed'     => '验证失败。',
        'change-held'           => '该变更正在等待审批。',
        'xref-not-found'        => '没有与此外部 ID 匹配的记录。',
        'entity-type-not-found' => '未找到实体类型。',
        'record-not-found'      => '未找到记录。',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => '实体类型',
            'source-system'       => '源系统',
            'code-list'           => '代码列表',
            'entity-type-info'    => '文件中记录所属的实体类型。',
            'source-system-info'  => '文件来源的系统。其外部 ID 从 external_id 列读取。',
            'code-list-info'      => '要导入的列表。代码会写入其草稿版本，需另行发布。',
            'mapping-info'        => '每个重命名的列一行：文件列=字段代码，按区域设置的字段写作 字段代码-区域设置，例如 Asset Num=external_id 和 Description=name。没有对应行的列按其自身名称读取。',
        ],

        'errors' => [
            'entity-type-invalid'                => '请选择一个您可以创建或编辑其记录的启用实体类型。',
            'source-system-invalid'              => '请选择一个启用的源系统。',
            'code-list-invalid'                  => '请选择一个您可以编辑的现有代码列表。',
            'external-id-without-source-system'  => '文件包含外部 ID 列（:column）。请选择其来源系统，或删除该列。',
        ],

        'schedule' => [
            'job-not-found'  => '没有代码为 :code 的导入任务。',
            'not-masterdata' => '任务 :code 不是主数据导入。',
            'no-user'        => '请通过 --user 或 MASTERDATA_IMPORT_USER 指定运行导入的管理员。',
            'user-not-found' => '没有邮箱为 :email 的活动管理员。',
            'file-not-found' => '在磁盘 :disk 上未找到文件 :path。',
            'nothing'        => '磁盘 :disk 的 :path 中没有等待处理的文件。',
            'queued'         => '已将 :file 加入任务 :code 的队列，跟踪编号 #:id。',
            'imported'       => '已为任务 :code 导入 :file，跟踪编号 #:id。',
        ],
    ],

    'demo' => [
        'seeded'           => '制造演示已就绪：:types 个实体类型、:systems 个源系统、站点树中的 :records 条记录、:xrefs 条交叉引用、:lists 个代码列表、:fields 个字段、:aliases 个别名。',
        'invalid-template' => '无法读取模板 :name，或其格式版本不受支持。',
    ],

    'platform-module' => [
        'name' => '主数据（MDM）',
    ],

    'entity-types' => [
        'mass-delete-success' => '已删除实体类型：:count 个。',
        'mass-delete-skipped' => '因仍有记录而未删除：:codes。',
        'datagrid'            => [
            'allowed-parents'      => '父类型',
            'records-count'        => '记录',
            'fields-count'         => '字段',
            'updated-at'           => '更新时间',
            'schema-version-value' => 'v:version',
            'sort-order'           => '排序',
        ],

        'edit' => [
            'schema-version' => '架构 v:version',
        ],

        'form' => [
            'basics'                    => '基本信息',
            'identity'                  => '标识',
            'hierarchy'                 => '层级',
            'governance'                => '治理',
            'quality-matching'          => '质量与匹配',
            'matching-info'             => '来自源系统的传入行会在匹配收件箱中与现有记录进行匹配。',
            'auto-match-threshold'      => '自动匹配阈值',
            'auto-match-off'            => '关闭',
            'auto-match-threshold-info' => '留空则逐行审核。取值 0.5 到 1：若某行的最佳建议得分不低于此值，且没有其他建议同样高，则自动接受。需要审批的类型仍会将关联保留待审批。',
            'match-on-write'            => '保存时检查重复',
            'match-on-write-info'       => '每条保存的记录都会在后台根据匹配规则与其他记录进行比较。关闭后仅在质量扫描中查找重复项。',
            'manage-icons'              => '管理图标',
            'sort-order'                => '排序',
            'sort-order-info'           => '数字越小越靠前，范围 0 到 9999；数字相同的类型按代码排序。',
        ],

        'quick-create' => [
            'code-info'             => '小写字母、数字和下划线，以字母开头。保存后无法更改。',
            'code-pattern-info'     => '使用 {seq} 或 {seq:6} 表示流水号，{TYPE} 表示类型代码，例如 {TYPE}-{seq:6}。',
            'mastering-styles-info' => [
                'consolidation' => '记录来自多个源系统，经过匹配后合并为一条黄金记录。',
                'centralized'   => '记录在此创建和维护，然后发布到使用它们的系统。',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => '基于 :target 的匹配规则',
        ],

        'show' => [
            'auto-matched' => '自动匹配',
            'auto-pending' => '已自动匹配；关联正在等待审批。',
        ],

        'datagrid' => [
            'decision' => '决策',
            'auto'     => '自动匹配',
            'manual'   => '手动',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => '使用方',
            'empty'        => '没有导出任务发布此类型和状态的记录。',
            'job'          => '导出任务',
            'format'       => '格式',
            'mode'         => '模式',
            'full'         => '全量',
            'delta'        => '仅变更',
            'last-run-at'  => '最近完成的运行',
            'no-run'       => '从未运行',
            'record-state' => '此记录',
            'states'       => [
                'never_run'  => '尚未导出',
                'pending'    => '自上次运行后已更改',
                'included'   => '已包含在上次运行中',
                'up_to_date' => '已是最新',
            ],
        ],
    ],

    'icons' => [
        'create-success' => '图标创建成功。',
        'update-success' => '图标更新成功。',
        'delete-success' => '图标删除成功。',
        'delete-in-use'  => '{1} 此图标正被一个实体类型使用，无法删除。|[2,*] 此图标正被 :count 个实体类型使用，无法删除。',
        'index'          => [
            'title'      => '图标',
            'create-btn' => '添加图标',
        ],

        'create' => [
            'title'    => '添加图标',
            'save-btn' => '保存图标',
        ],

        'edit' => [
            'title'    => '编辑图标',
            'save-btn' => '保存图标',
        ],

        'form' => [
            'code'      => '代码',
            'name'      => '名称',
            'file'      => '图标文件',
            'file-info' => 'SVG、PNG 或 WebP，最大 :size KB。请在透明背景上使用单一形状：它会采用主题颜色。',
            'preview'   => '预览',
            'usage'     => '{0} 尚无实体类型使用此图标。|{1} 被一个实体类型使用。|[2,*] 被 :count 个实体类型使用。',
        ],

        'library' => [
            'barcode'    => '条形码',
            'boxes'      => '物料',
            'briefcase'  => '供应商',
            'building_2' => '公司',
            'cpu'        => '资产',
            'factory'    => '工厂',
            'file_text'  => '合同',
            'globe'      => '国家',
            'handshake'  => '合作伙伴',
            'landmark'   => '法人实体',
            'map_pin'    => '位置',
            'network'    => '层级',
            'package'    => '产品',
            'store'      => '门店',
            'tag'        => '类别',
            'truck'      => '供货商',
            'user_round' => '联系人',
            'users'      => '客户',
            'warehouse'  => '仓库',
            'wrench'     => '设备',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => '图标',
            'code'    => '代码',
            'name'    => '名称',
            'usage'   => '使用方',
            'edit'    => '编辑',
            'delete'  => '删除',
        ],
    ],

    'match-rules' => [
        'tab'            => '匹配',
        'title'          => '匹配规则',
        'info'           => '设置如何比较此类型的记录，以查找重复项并匹配传入的行。没有规则时，将比较代码和标签。',
        'create-btn'     => '添加匹配规则',
        'create-success' => '匹配规则创建成功。',
        'update-success' => '匹配规则更新成功。',
        'delete-success' => '匹配规则删除成功。',

        'create' => [
            'title' => '添加匹配规则',
        ],

        'edit' => [
            'title' => '编辑匹配规则',
        ],

        'form' => [
            'target'            => '比较对象',
            'select-target'     => '选择要比较的内容',
            'target-info'       => '记录代码、其标签或此实体类型的某个字段。',
            'comparator'        => '比较方式',
            'select-comparator' => '选择比较方式',
            'comparator-info'   => '两个值的比较方式，例如“模糊”用于有拼写错误的名称，“数值”用于金额。',
            'weight'            => '权重',
            'weight-info'       => '此规则所占的比重，取值 0.1 到 100。得分是传入行有值的那些规则的加权平均值。',
            'tolerance'         => '容差',
            'tolerance-info'    => '两个数字之间仍视为相似的最大差值。留空则只匹配相等的数字。',
            'days'              => '日期范围（天）',
            'days-info'         => '两个日期之间仍视为相似的最大相差天数。留空则只匹配同一天。',
            'sort-order'        => '排序',
            'is-blocking'       => '分块键',
            'is-blocking-info'  => '同时查找与此值完全相同的记录，即使其代码和标签看起来不同。字段必须可搜索。',
            'save-btn'          => '保存匹配规则',
        ],

        'targets' => [
            'code'  => '代码',
            'label' => '标签',
        ],

        'comparators' => [
            'exact'    => '精确',
            'fuzzy'    => '模糊',
            'token'    => '相同词语',
            'phonetic' => '读音相近',
            'numeric'  => '数值',
            'date'     => '日期',
        ],

        'datagrid' => [
            'target'     => '比较对象',
            'comparator' => '比较方式',
            'weight'     => '权重',
            'blocking'   => '分块键',
            'sort-order' => '排序',
            'yes'        => '是',
            'no'         => '否',
            'edit'       => '编辑',
            'delete'     => '删除',
        ],

        'validation' => [
            'blocking-not-searchable' => '只有代码、标签或可搜索字段才能作为分块键。',
            'too-many'                => '一个实体类型最多可有 :count 条匹配规则。',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => '存续规则',
        'title'          => '存续规则',
        'info'           => '决定合并此类型的两条记录时，黄金记录保留哪个值。没有规则时，保留下来的记录会保持其值，除非该值为空。',
        'create-btn'     => '添加存续规则',
        'create-success' => '存续规则创建成功。',
        'update-success' => '存续规则更新成功。',
        'delete-success' => '存续规则删除成功。',

        'create' => [
            'title' => '添加存续规则',
        ],

        'edit' => [
            'title' => '编辑存续规则',
        ],

        'form' => [
            'target'            => '适用于',
            'select-target'     => '选择规则的适用对象',
            'target-info'       => '某个字段、记录标签，或所有没有专属规则的字段。',
            'strategy'          => '策略',
            'select-strategy'   => '选择策略',
            'strategy-info'     => '如何选取保留的值。数据管家仍可在合并界面更改每项选择。',
            'source-order'      => '来源顺序',
            'select-sources'    => '选择源系统',
            'source-order-info' => '最可信的源系统排在最前。未列出的系统按各自优先级排序。留空则仅使用源系统优先级。',
            'sort-order'        => '排序',
            'save-btn'          => '保存存续规则',
        ],

        'targets' => [
            'default' => '所有字段的默认规则',
            'label'   => '标签',
        ],

        'strategies' => [
            'source_priority' => '最可信来源',
            'most_recent'     => '最近更新',
            'most_complete'   => '最完整',
            'keep_target'     => '保留幸存记录',
            'manual'          => '由数据管家决定',
        ],

        'datagrid' => [
            'target'     => '适用于',
            'strategy'   => '策略',
            'sort-order' => '排序',
            'edit'       => '编辑',
            'delete'     => '删除',
        ],

        'validation' => [
            'target-taken' => '此实体类型已有针对该目标的存续规则。',
            'too-many'     => '一个实体类型最多可有 :count 条存续规则。',
        ],
    ],

    'hierarchy' => [
        'browse-children' => '浏览下级:type',
    ],

    'records' => [
        'index' => [
            'top-level' => '顶层',
        ],

        'datagrid' => [
            'erase'         => '永久清除',
            'parent'        => '上级',
            'top-level'     => '顶层',
            'children'      => '下级',
            'view-children' => '显示此记录下的 :count 条记录',
        ],

        'erase' => [
            'btn'             => '永久清除',
            'confirm-title'   => '永久清除此记录？',
            'confirm-message' => '此记录及合并到其中的重复记录的所有值、标签和代码，以及它们的历史、变更请求和质量问题都将被删除。记录会作为已清除的占位符保留，以便其来源 ID 和关联仍可解析。此操作无法撤销。',
            'success'         => '记录已永久清除。',
            'not-retired'     => '只能清除已停用的记录。请先停用它。',
        ],

        'mass-retire' => [
            'success' => '已停用 :count 条记录。',
            'pending' => '已提交 :count 个停用请求等待审批。',
            'skipped' => '已跳过 :count 条记录，因为它们不是草稿或启用状态，或已被他人修改。',
            'none'    => '所选记录均无法停用。',
        ],
    ],

    'quality-dashboard' => [
        'menu'     => '质量仪表板',
        'acl'      => '质量仪表板',
        'index'    => [
            'title'         => '数据质量仪表板',
            'info'          => '按实体类型每日记录完整性、有效性和重复项快照，并显示每位数据管理员的待办工作。',
            'empty'         => '没有可报告的实体类型。',
            'scorecards'    => '记分卡',
            'no-snapshot'   => '尚未生成快照。',
            'not-available' => '无',
            'percent'       => ':value%',
            'delta'         => ':value 个百分点',
            'records'       => '记录：:count',
            'open-tasks'    => '未完成任务：:count',
            'snapshot-date' => ':date 的快照',
            'trend'         => '趋势：:type',
            'trend-empty'   => '此范围内尚无快照。',
            'chart-label'   => ':type 从 :from 到 :to 的数据质量趋势',
            'workload'      => '管理员工作量',
        ],
        'ranges' => [
            '7'  => '最近 7 天',
            '30' => '最近 30 天',
            '90' => '最近 90 天',
        ],
        'metrics' => [
            'completeness_avg' => '完整性',
            'validity_rate'    => '有效性',
            'duplicate_rate'   => '重复项',
        ],
        'workload' => [
            'steward'          => '数据管理员',
            'change-requests'  => '未处理的变更请求',
            'match-candidates' => '未处理的匹配候选',
            'unassigned'       => '无数据所有者',
            'empty'            => '数据管理员没有未完成的任务。',
            'buckets'          => [
                'day'   => '少于 1 天',
                'week'  => '1 至 7 天',
                'month' => '7 至 30 天',
                'older' => '超过 30 天',
            ],
        ],
        'snapshot' => [
            'queued' => '质量快照采集已加入队列。',
            'done'   => '已采集 :count 个实体类型的质量快照。',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => '需要审批：变更请求 #:id',
            'description'           => ':requester 提交了对 :type 的“:operation”变更，等待您审批。',
            'description-automated' => '对 :type 的自动“:operation”变更正在等待您审批。',
        ],
    ],

    'access' => [
        'menu'           => '数据访问',
        'acl'            => [
            'index'  => '数据访问',
            'create' => '创建',
            'edit'   => '编辑',
            'delete' => '删除',
        ],
        'principals' => [
            'role' => '角色',
            'user' => '用户',
        ],
        'actions' => [
            'view'    => '查看',
            'edit'    => '编辑',
            'approve' => '审批',
        ],
        'index' => [
            'title'      => '数据访问',
            'create-btn' => '添加授权',
            'info'       => '授权决定每个角色或用户可以访问哪些受限实体类型的记录：所选记录及其下的所有记录。来自用户角色的访问权限与直接授予用户的权限会叠加。',
        ],
        'create' => [
            'title' => '添加数据访问授权',
        ],
        'edit' => [
            'title' => '编辑数据访问授权',
        ],
        'form' => [
            'save-btn'         => '保存授权',
            'principal'        => '授权对象',
            'principal-info'   => '角色授权适用于拥有该角色的所有用户。如需例外，请单独授权给某个用户。',
            'principal-type'   => '授权类型',
            'role'             => '角色',
            'user'             => '用户',
            'scope'            => '范围',
            'scope-info'       => '该授权涵盖所选记录及其在层级结构中的所有下级记录。',
            'root'             => '子树根节点',
            'root-info'        => '例如某个站点或工厂。其下的记录会继承该授权。',
            'entity-type'      => '实体类型',
            'entity-type-info' => '将授权限定为某一个受限实体类型，留空则适用于所有受限类型。',
            'any-entity-type'  => '所有受限实体类型',
            'actions'          => '操作',
            'actions-info'     => '“查看”可读取记录；“编辑”可在根节点下修改、停用、合并和创建记录；“审批”可决定被暂扣的变更。“编辑”和“审批”均包含“查看”。',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => '类型',
            'principal'         => '授权对象',
            'missing-principal' => '已删除',
            'entity-type'       => '实体类型',
            'root'              => '子树根节点',
            'actions'           => '操作',
            'edit'              => '编辑',
            'delete'            => '删除',
        ],
        'role-card' => [
            'title'  => '主数据访问',
            'info'   => '拥有此角色的用户可以访问哪些受限实体类型的记录。每项授权涵盖其根记录及其下的所有记录。',
            'empty'  => '此角色尚无主数据访问授权。',
            'remove' => '移除',
            'add'    => '添加授权',
        ],
        'entity-type' => [
            'is-scoped'      => '按子树限制访问',
            'is-scoped-info' => '开启后，用户只能访问在“数据访问”页面中授予其角色或其本人的子树内的该类型记录。共享参考数据请保持关闭。',
        ],
        'validation' => [
            'record-not-found'    => '请选择一条您可以查看的记录作为子树根节点。',
            'parent-out-of-scope' => '请在您拥有编辑权限的子树内选择父级。',
        ],
        'create-success' => '已添加数据访问授权。',
        'update-success' => '已更新数据访问授权。',
        'delete-success' => '已删除数据访问授权。',
    ],

    'install' => [
        'migrating' => '正在运行主数据迁移。',
        'demo'      => '正在安装制造业演示数据。',
        'done'      => '主数据管理已安装。已启用的模块::modules。',
    ],
];
