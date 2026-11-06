<?php

return [
    'menu' => [
        'master-data' => '主資料',
        'icons'       => '圖示',
    ],

    'acl' => [
        'master-data' => '主資料',
        'icons'       => [
            'index'  => '圖示',
            'create' => '建立',
            'edit'   => '編輯',
            'delete' => '刪除',
        ],

        'records' => [
            'erase' => '永久清除',
        ],
    ],

    'dashboard' => [
        'title'      => '主資料',
        'inside'     => '隸屬於：:types',
        'top-level'  => '頂層',
        'standalone' => '獨立',
    ],

    'api' => [
        'acl' => [
            'master-data' => '主資料',
            'records'     => '記錄',
            'upsert'      => '依來源系統 ID 新增或更新',
        ],

        'validation-failed'     => '驗證失敗。',
        'change-held'           => '此變更正在等待核准。',
        'xref-not-found'        => '沒有與此外部 ID 相符的記錄。',
        'entity-type-not-found' => '找不到實體類型。',
        'record-not-found'      => '找不到記錄。',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => '實體類型',
            'source-system'       => '來源系統',
            'code-list'           => '代碼清單',
            'entity-type-info'    => '檔案中記錄所屬的實體類型。',
            'source-system-info'  => '檔案來源的系統。其外部 ID 從 external_id 欄讀取。',
            'code-list-info'      => '要匯入的清單。代碼會寫入其草稿版本，需另行發布。',
            'mapping-info'        => '每個重新命名的欄一行：檔案欄=欄位代碼，依語系的欄位寫作 欄位代碼-語系，例如 Asset Num=external_id 和 Description=name。沒有對應行的欄依其自身名稱讀取。',
        ],

        'errors' => [
            'entity-type-invalid'                => '請選擇一個您可以建立或編輯其記錄的啟用實體類型。',
            'source-system-invalid'              => '請選擇一個啟用的來源系統。',
            'code-list-invalid'                  => '請選擇一個您可以編輯的現有代碼清單。',
            'external-id-without-source-system'  => '檔案包含外部 ID 欄（:column）。請選擇其來源系統，或刪除該欄。',
        ],

        'schedule' => [
            'job-not-found'  => '沒有代碼為 :code 的匯入工作。',
            'not-masterdata' => '工作 :code 不是主資料匯入。',
            'no-user'        => '請透過 --user 或 MASTERDATA_IMPORT_USER 指定執行匯入的管理員。',
            'user-not-found' => '沒有電子郵件為 :email 的啟用中管理員。',
            'file-not-found' => '在磁碟 :disk 上找不到檔案 :path。',
            'nothing'        => '磁碟 :disk 的 :path 中沒有等待處理的檔案。',
            'queued'         => '已將 :file 加入工作 :code 的佇列，追蹤編號 #:id。',
            'imported'       => '已為工作 :code 匯入 :file，追蹤編號 #:id。',
        ],
    ],

    'demo' => [
        'seeded'           => '製造示範已就緒：:types 個實體類型、:systems 個來源系統、站點樹中的 :records 筆記錄、:xrefs 筆交叉參照、:lists 個代碼清單、:fields 個欄位、:aliases 個別名。',
        'invalid-template' => '無法讀取範本 :name，或其格式版本不受支援。',
    ],

    'platform-module' => [
        'name' => '主資料（MDM）',
    ],

    'entity-types' => [
        'mass-delete-success' => '已刪除實體類型：:count 個。',
        'mass-delete-skipped' => '因仍有記錄而未刪除：:codes。',
        'datagrid'            => [
            'allowed-parents'      => '父類型',
            'records-count'        => '記錄',
            'fields-count'         => '欄位',
            'updated-at'           => '更新時間',
            'schema-version-value' => 'v:version',
            'sort-order'           => '排序',
        ],

        'edit' => [
            'schema-version' => '結構描述 v:version',
        ],

        'form' => [
            'basics'                    => '基本資訊',
            'identity'                  => '識別',
            'hierarchy'                 => '階層',
            'governance'                => '治理',
            'quality-matching'          => '品質與比對',
            'matching-info'             => '來自來源系統的傳入列會在比對收件匣中與現有記錄進行比對。',
            'auto-match-threshold'      => '自動比對門檻',
            'auto-match-off'            => '關閉',
            'auto-match-threshold-info' => '留空則逐列審核。取值 0.5 到 1：若某列的最佳建議分數不低於此值，且沒有其他建議同樣高，則自動接受。需要核准的類型仍會將連結保留待核准。',
            'match-on-write'            => '儲存時檢查重複',
            'match-on-write-info'       => '每筆儲存的記錄都會在背景中依比對規則與其他記錄進行比較。關閉後僅在品質掃描中尋找重複項目。',
            'manage-icons'              => '管理圖示',
            'sort-order'                => '排序',
            'sort-order-info'           => '數字越小越靠前，範圍 0 到 9999；數字相同的類型依代碼排序。',
        ],

        'quick-create' => [
            'code-info'             => '小寫字母、數字和底線，以字母開頭。儲存後無法變更。',
            'code-pattern-info'     => '使用 {seq} 或 {seq:6} 表示流水號，{TYPE} 表示類型代碼，例如 {TYPE}-{seq:6}。',
            'mastering-styles-info' => [
                'consolidation' => '記錄來自多個來源系統，經過比對後合併為一筆黃金記錄。',
                'centralized'   => '記錄在此建立和維護，然後發布到使用它們的系統。',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => '依 :target 的比對規則',
        ],

        'show' => [
            'auto-matched' => '自動比對',
            'auto-pending' => '已自動比對；連結正在等待核准。',
        ],

        'datagrid' => [
            'decision' => '決策',
            'auto'     => '自動比對',
            'manual'   => '手動',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => '使用者',
            'empty'        => '沒有匯出工作發布此類型與狀態的記錄。',
            'job'          => '匯出工作',
            'format'       => '格式',
            'mode'         => '模式',
            'full'         => '完整',
            'delta'        => '僅變更',
            'last-run-at'  => '最近完成的執行',
            'no-run'       => '從未執行',
            'record-state' => '此記錄',
            'states'       => [
                'never_run'  => '尚未匯出',
                'pending'    => '自上次執行後已變更',
                'included'   => '已包含在上次執行中',
                'up_to_date' => '已是最新',
            ],
        ],
    ],

    'icons' => [
        'create-success' => '圖示建立成功。',
        'update-success' => '圖示更新成功。',
        'delete-success' => '圖示刪除成功。',
        'delete-in-use'  => '{1} 此圖示正被一個實體類型使用，無法刪除。|[2,*] 此圖示正被 :count 個實體類型使用，無法刪除。',
        'index'          => [
            'title'      => '圖示',
            'create-btn' => '新增圖示',
        ],

        'create' => [
            'title'    => '新增圖示',
            'save-btn' => '儲存圖示',
        ],

        'edit' => [
            'title'    => '編輯圖示',
            'save-btn' => '儲存圖示',
        ],

        'form' => [
            'code'      => '代碼',
            'name'      => '名稱',
            'file'      => '圖示檔案',
            'file-info' => 'SVG、PNG 或 WebP，最大 :size KB。請在透明背景上使用單一形狀：它會採用主題顏色。',
            'preview'   => '預覽',
            'usage'     => '{0} 尚無實體類型使用此圖示。|{1} 被一個實體類型使用。|[2,*] 被 :count 個實體類型使用。',
        ],

        'library' => [
            'barcode'    => '條碼',
            'boxes'      => '物料',
            'briefcase'  => '供應商',
            'building_2' => '公司',
            'cpu'        => '資產',
            'factory'    => '工廠',
            'file_text'  => '合約',
            'globe'      => '國家',
            'handshake'  => '合作夥伴',
            'landmark'   => '法人實體',
            'map_pin'    => '位置',
            'network'    => '階層',
            'package'    => '產品',
            'store'      => '門市',
            'tag'        => '類別',
            'truck'      => '供貨商',
            'user_round' => '聯絡人',
            'users'      => '客戶',
            'warehouse'  => '倉庫',
            'wrench'     => '設備',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => '圖示',
            'code'    => '代碼',
            'name'    => '名稱',
            'usage'   => '使用方',
            'edit'    => '編輯',
            'delete'  => '刪除',
        ],
    ],

    'match-rules' => [
        'tab'            => '比對',
        'title'          => '比對規則',
        'info'           => '設定如何比較此類型的記錄，以找出重複項目並比對傳入的資料列。沒有規則時，會比較代碼和標籤。',
        'create-btn'     => '新增比對規則',
        'create-success' => '比對規則建立成功。',
        'update-success' => '比對規則更新成功。',
        'delete-success' => '比對規則刪除成功。',

        'create' => [
            'title' => '新增比對規則',
        ],

        'edit' => [
            'title' => '編輯比對規則',
        ],

        'form' => [
            'target'            => '比較對象',
            'select-target'     => '選擇要比較的內容',
            'target-info'       => '記錄代碼、其標籤或此實體類型的某個欄位。',
            'comparator'        => '比較方式',
            'select-comparator' => '選擇比較方式',
            'comparator-info'   => '兩個值的比較方式，例如「模糊」適用於有拼字錯誤的名稱，「數值」適用於金額。',
            'weight'            => '權重',
            'weight-info'       => '此規則所佔的比重，範圍 0.1 到 100。分數是傳入資料列有值的那些規則的加權平均。',
            'tolerance'         => '容許誤差',
            'tolerance-info'    => '兩個數字之間仍視為相似的最大差距。留空則只比對相等的數字。',
            'days'              => '日期範圍（天）',
            'days-info'         => '兩個日期之間仍視為相似的最大相差天數。留空則只比對同一天。',
            'sort-order'        => '排序',
            'is-blocking'       => '分塊鍵',
            'is-blocking-info'  => '同時查找與此值完全相同的記錄，即使其代碼和標籤看起來不同。欄位必須可供搜尋。',
            'save-btn'          => '儲存比對規則',
        ],

        'targets' => [
            'code'  => '代碼',
            'label' => '標籤',
        ],

        'comparators' => [
            'exact'    => '完全相符',
            'fuzzy'    => '模糊',
            'token'    => '相同字詞',
            'phonetic' => '讀音相近',
            'numeric'  => '數值',
            'date'     => '日期',
        ],

        'datagrid' => [
            'target'     => '比較對象',
            'comparator' => '比較方式',
            'weight'     => '權重',
            'blocking'   => '分塊鍵',
            'sort-order' => '排序',
            'yes'        => '是',
            'no'         => '否',
            'edit'       => '編輯',
            'delete'     => '刪除',
        ],

        'validation' => [
            'blocking-not-searchable' => '只有代碼、標籤或可搜尋的欄位才能作為分塊鍵。',
            'too-many'                => '一個實體類型最多可有 :count 條比對規則。',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => '存續規則',
        'title'          => '存續規則',
        'info'           => '決定合併此類型的兩筆記錄時，黃金記錄保留哪個值。沒有規則時，留下的記錄會保留其值，除非該值為空。',
        'create-btn'     => '新增存續規則',
        'create-success' => '存續規則建立成功。',
        'update-success' => '存續規則更新成功。',
        'delete-success' => '存續規則刪除成功。',

        'create' => [
            'title' => '新增存續規則',
        ],

        'edit' => [
            'title' => '編輯存續規則',
        ],

        'form' => [
            'target'            => '適用於',
            'select-target'     => '選擇規則的適用對象',
            'target-info'       => '某個欄位、記錄標籤，或所有沒有專屬規則的欄位。',
            'strategy'          => '策略',
            'select-strategy'   => '選擇策略',
            'strategy-info'     => '如何選取保留的值。資料管家仍可在合併畫面變更每項選擇。',
            'source-order'      => '來源順序',
            'select-sources'    => '選擇來源系統',
            'source-order-info' => '最可信的來源系統排在最前。未列出的系統依各自優先順序排列。留空則僅使用來源系統優先順序。',
            'sort-order'        => '排序',
            'save-btn'          => '儲存存續規則',
        ],

        'targets' => [
            'default' => '所有欄位的預設規則',
            'label'   => '標籤',
        ],

        'strategies' => [
            'source_priority' => '最可信來源',
            'most_recent'     => '最近更新',
            'most_complete'   => '最完整',
            'keep_target'     => '保留倖存記錄',
            'manual'          => '由資料管家決定',
        ],

        'datagrid' => [
            'target'     => '適用於',
            'strategy'   => '策略',
            'sort-order' => '排序',
            'edit'       => '編輯',
            'delete'     => '刪除',
        ],

        'validation' => [
            'target-taken' => '此實體類型已有針對該目標的存續規則。',
            'too-many'     => '一個實體類型最多可有 :count 條存續規則。',
        ],
    ],

    'hierarchy' => [
        'browse-children' => '瀏覽下層:type',
    ],

    'records' => [
        'index' => [
            'top-level' => '頂層',
        ],

        'datagrid' => [
            'erase'         => '永久清除',
            'parent'        => '上層',
            'top-level'     => '頂層',
            'children'      => '下層',
            'view-children' => '顯示此紀錄下的 :count 筆紀錄',
        ],

        'erase' => [
            'btn'             => '永久清除',
            'confirm-title'   => '要永久清除此記錄嗎？',
            'confirm-message' => '此記錄及合併到其中的重複記錄的所有值、標籤和代碼，以及其歷史、變更請求和品質問題都會被刪除。記錄會保留為已清除的預留位置，讓其來源 ID 和關聯仍可解析。此操作無法復原。',
            'success'         => '記錄已永久清除。',
            'not-retired'     => '只能清除已停用的記錄。請先停用。',
        ],

        'mass-retire' => [
            'success' => '已停用 :count 筆記錄。',
            'pending' => '已提交 :count 個停用請求等待核准。',
            'skipped' => '已略過 :count 筆記錄，因為它們不是草稿或啟用狀態，或已被他人修改。',
            'none'    => '所選記錄皆無法停用。',
        ],
    ],

    'quality-dashboard' => [
        'menu'     => '品質儀表板',
        'acl'      => '品質儀表板',
        'index'    => [
            'title'         => '資料品質儀表板',
            'info'          => '依實體類型每日記錄完整性、有效性與重複項快照，並顯示每位資料管理員的待辦工作。',
            'empty'         => '沒有可報告的實體類型。',
            'scorecards'    => '計分卡',
            'no-snapshot'   => '尚未產生快照。',
            'not-available' => '無',
            'percent'       => ':value%',
            'delta'         => ':value 個百分點',
            'records'       => '記錄：:count',
            'open-tasks'    => '未完成任務：:count',
            'snapshot-date' => ':date 的快照',
            'trend'         => '趨勢：:type',
            'trend-empty'   => '此範圍內尚無快照。',
            'chart-label'   => ':type 從 :from 到 :to 的資料品質趨勢',
            'workload'      => '管理員工作量',
        ],
        'ranges' => [
            '7'  => '最近 7 天',
            '30' => '最近 30 天',
            '90' => '最近 90 天',
        ],
        'metrics' => [
            'completeness_avg' => '完整性',
            'validity_rate'    => '有效性',
            'duplicate_rate'   => '重複項',
        ],
        'workload' => [
            'steward'          => '資料管理員',
            'change-requests'  => '未處理的變更請求',
            'match-candidates' => '未處理的比對候選',
            'unassigned'       => '無資料擁有者',
            'empty'            => '資料管理員沒有未完成的任務。',
            'buckets'          => [
                'day'   => '少於 1 天',
                'week'  => '1 至 7 天',
                'month' => '7 至 30 天',
                'older' => '超過 30 天',
            ],
        ],
        'snapshot' => [
            'queued' => '品質快照擷取已加入佇列。',
            'done'   => '已擷取 :count 個實體類型的品質快照。',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => '需要核准：變更請求 #:id',
            'description'           => ':requester 提交了對 :type 的「:operation」變更，等待您核准。',
            'description-automated' => '對 :type 的自動「:operation」變更正在等待您核准。',
        ],
    ],

    'access' => [
        'menu'           => '資料存取',
        'acl'            => [
            'index'  => '資料存取',
            'create' => '建立',
            'edit'   => '編輯',
            'delete' => '刪除',
        ],
        'principals' => [
            'role' => '角色',
            'user' => '使用者',
        ],
        'actions' => [
            'view'    => '檢視',
            'edit'    => '編輯',
            'approve' => '核准',
        ],
        'index' => [
            'title'      => '資料存取',
            'create-btn' => '新增授權',
            'info'       => '授權決定每個角色或使用者可以存取哪些受限實體類型的記錄：所選記錄及其下的所有記錄。來自使用者角色的存取權限與直接授予使用者的權限會疊加。',
        ],
        'create' => [
            'title' => '新增資料存取授權',
        ],
        'edit' => [
            'title' => '編輯資料存取授權',
        ],
        'form' => [
            'save-btn'         => '儲存授權',
            'principal'        => '授權對象',
            'principal-info'   => '角色授權適用於擁有該角色的所有使用者。如需例外，請單獨授權給某位使用者。',
            'principal-type'   => '授權類型',
            'role'             => '角色',
            'user'             => '使用者',
            'scope'            => '範圍',
            'scope-info'       => '此授權涵蓋所選記錄及其在階層中的所有下層記錄。',
            'root'             => '子樹根節點',
            'root-info'        => '例如某個據點或工廠。其下的記錄會繼承此授權。',
            'entity-type'      => '實體類型',
            'entity-type-info' => '將授權限定為某一個受限實體類型，留空則適用於所有受限類型。',
            'any-entity-type'  => '所有受限實體類型',
            'actions'          => '操作',
            'actions-info'     => '「檢視」可讀取記錄；「編輯」可在根節點下修改、停用、合併及建立記錄；「核准」可決定被暫扣的變更。「編輯」與「核准」皆包含「檢視」。',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => '類型',
            'principal'         => '授權對象',
            'missing-principal' => '已刪除',
            'entity-type'       => '實體類型',
            'root'              => '子樹根節點',
            'actions'           => '操作',
            'edit'              => '編輯',
            'delete'            => '刪除',
        ],
        'role-card' => [
            'title'  => '主資料存取',
            'info'   => '擁有此角色的使用者可以存取哪些受限實體類型的記錄。每項授權涵蓋其根記錄及其下的所有記錄。',
            'empty'  => '此角色尚無主資料存取授權。',
            'remove' => '移除',
            'add'    => '新增授權',
        ],
        'entity-type' => [
            'is-scoped'      => '依子樹限制存取',
            'is-scoped-info' => '開啟後，使用者只能存取在「資料存取」畫面中授予其角色或其本人的子樹內的此類型記錄。共用參考資料請保持關閉。',
        ],
        'validation' => [
            'record-not-found'    => '請選擇一筆您可以檢視的記錄作為子樹根節點。',
            'parent-out-of-scope' => '請在您擁有編輯權限的子樹內選擇父層。',
        ],
        'create-success' => '已新增資料存取授權。',
        'update-success' => '已更新資料存取授權。',
        'delete-success' => '已刪除資料存取授權。',
    ],

    'install' => [
        'migrating' => '正在執行主資料遷移。',
        'demo'      => '正在安裝製造業示範資料。',
        'done'      => '主資料管理已安裝。啟用的模組::modules。',
    ],
];
