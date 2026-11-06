<?php

return [
    'menu' => [
        'master-data' => '마스터 데이터',
        'icons'       => '아이콘',
    ],

    'acl' => [
        'master-data' => '마스터 데이터',
        'icons'       => [
            'index'  => '아이콘',
            'create' => '만들기',
            'edit'   => '편집',
            'delete' => '삭제',
        ],

        'records' => [
            'erase' => '영구 삭제',
        ],
    ],

    'dashboard' => [
        'title'      => '마스터 데이터',
        'inside'     => '상위: :types',
        'top-level'  => '최상위',
        'standalone' => '독립형',
    ],

    'api' => [
        'acl' => [
            'master-data' => '마스터 데이터',
            'records'     => '레코드',
            'upsert'      => '소스 시스템 ID 기준으로 추가 또는 업데이트',
        ],

        'validation-failed'     => '유효성 검사에 실패했습니다.',
        'change-held'           => '변경 사항이 승인을 기다리고 있습니다.',
        'xref-not-found'        => '이 외부 ID와 일치하는 레코드가 없습니다.',
        'entity-type-not-found' => '엔터티 유형을 찾을 수 없습니다.',
        'record-not-found'      => '레코드를 찾을 수 없습니다.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => '엔티티 유형',
            'source-system'       => '소스 시스템',
            'code-list'           => '코드 목록',
            'entity-type-info'    => '파일에 포함된 레코드의 엔티티 유형입니다.',
            'source-system-info'  => '파일을 가져온 시스템입니다. 외부 ID는 external_id 열에서 읽습니다.',
            'code-list-info'      => '가져올 대상 목록입니다. 코드는 초안 버전에 기록되며, 별도로 게시합니다.',
            'mapping-info'        => '이름을 바꾼 열마다 한 줄: 파일 열=필드 코드, 또는 로케일별 필드의 경우 필드 코드-로케일 (예: Asset Num=external_id, Description=name). 줄이 없는 열은 자체 이름으로 읽습니다.',
        ],

        'errors' => [
            'entity-type-invalid'                => '레코드를 생성하거나 편집할 수 있는 활성 엔티티 유형을 선택하세요.',
            'source-system-invalid'              => '활성 소스 시스템을 선택하세요.',
            'code-list-invalid'                  => '편집할 수 있는 기존 코드 목록을 선택하세요.',
            'external-id-without-source-system'  => '파일에 외부 ID 열(:column)이 있습니다. 해당 소스 시스템을 선택하거나 열을 제거하세요.',
        ],

        'schedule' => [
            'job-not-found'  => '코드가 :code인 가져오기 작업이 없습니다.',
            'not-masterdata' => '작업 :code은(는) 마스터 데이터 가져오기가 아닙니다.',
            'no-user'        => '--user 또는 MASTERDATA_IMPORT_USER로 가져오기를 실행할 관리자를 지정하세요.',
            'user-not-found' => '이메일이 :email인 활성 관리자가 없습니다.',
            'file-not-found' => '디스크 :disk에서 파일 :path을(를) 찾을 수 없습니다.',
            'nothing'        => '디스크 :disk의 :path에 대기 중인 파일이 없습니다.',
            'queued'         => '작업 :code에 대해 :file을(를) 대기열에 추가했습니다. 추적 번호: #:id.',
            'imported'       => '작업 :code에 대해 :file을(를) 가져왔습니다. 추적 번호: #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => '제조 데모가 준비되었습니다. 엔터티 유형 :types개, 소스 시스템 :systems개, 사이트 트리의 레코드 :records개, 교차 참조 :xrefs개, 코드 목록 :lists개, 필드 :fields개, 별칭 :aliases개.',
        'invalid-template' => '템플릿 :name을(를) 읽을 수 없거나 지원되지 않는 형식 버전입니다.',
    ],

    'platform-module' => [
        'name' => '마스터 데이터(MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => '삭제된 엔터티 유형: :count개.',
        'mass-delete-skipped' => '레코드가 남아 있어 삭제되지 않았습니다: :codes.',
        'datagrid'            => [
            'allowed-parents'      => '상위 유형',
            'records-count'        => '레코드',
            'fields-count'         => '필드',
            'updated-at'           => '업데이트됨',
            'schema-version-value' => 'v:version',
            'sort-order'           => '정렬 순서',
        ],

        'edit' => [
            'schema-version' => '스키마 v:version',
        ],

        'form' => [
            'basics'                    => '기본 정보',
            'identity'                  => '식별',
            'hierarchy'                 => '계층',
            'governance'                => '거버넌스',
            'quality-matching'          => '품질 및 매칭',
            'matching-info'             => '소스 시스템에서 들어온 행은 매칭 받은편지함에서 기존 레코드와 매칭됩니다.',
            'auto-match-threshold'      => '자동 매칭 임계값',
            'auto-match-off'            => '끔',
            'auto-match-threshold-info' => '모든 행을 검토하려면 비워 두세요. 0.5~1: 가장 좋은 제안의 점수가 이 값 이상이고 다른 제안이 그만큼 높지 않으면 행이 자동으로 수락됩니다. 승인이 필요한 유형은 여전히 연결을 승인 대기 상태로 둡니다.',
            'match-on-write'            => '저장 시 중복 확인',
            'match-on-write-info'       => '저장된 각 레코드는 매칭 규칙을 사용해 백그라운드에서 다른 레코드와 비교됩니다. 품질 검사에서만 중복을 찾으려면 끄세요.',
            'manage-icons'              => '아이콘 관리',
            'sort-order'                => '정렬 순서',
            'sort-order-info'           => '작은 숫자가 먼저 표시됩니다(0~9999). 같은 숫자의 유형은 코드순으로 표시됩니다.',
        ],

        'quick-create' => [
            'code-info'             => '영문 소문자, 숫자, 밑줄을 사용하며 문자로 시작해야 합니다. 저장 후에는 변경할 수 없습니다.',
            'code-pattern-info'     => '일련번호에는 {seq} 또는 {seq:6}, 유형 코드에는 {TYPE}를 사용하세요. 예: {TYPE}-{seq:6}',
            'mastering-styles-info' => [
                'consolidation' => '레코드는 여러 소스 시스템에서 들어오며, 매칭 후 하나의 골든 레코드로 병합됩니다.',
                'centralized'   => '레코드는 여기에서 생성·관리된 후 이를 사용하는 시스템에 게시됩니다.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => ':target 매칭 규칙',
        ],

        'show' => [
            'auto-matched' => '자동 매칭됨',
            'auto-pending' => '자동으로 매칭되었습니다. 연결이 승인을 기다리고 있습니다.',
        ],

        'datagrid' => [
            'decision' => '결정',
            'auto'     => '자동 매칭됨',
            'manual'   => '수동',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => '사용처',
            'empty'        => '이 유형과 상태의 레코드를 게시하는 내보내기 작업이 없습니다.',
            'job'          => '내보내기 작업',
            'format'       => '형식',
            'mode'         => '모드',
            'full'         => '전체',
            'delta'        => '변경분만',
            'last-run-at'  => '마지막 완료 실행',
            'no-run'       => '실행된 적 없음',
            'record-state' => '이 레코드',
            'states'       => [
                'never_run'  => '아직 내보내지 않음',
                'pending'    => '마지막 실행 이후 변경됨',
                'included'   => '마지막 실행에 포함됨',
                'up_to_date' => '최신 상태',
            ],
        ],
    ],

    'icons' => [
        'create-success' => '아이콘을 만들었습니다.',
        'update-success' => '아이콘을 업데이트했습니다.',
        'delete-success' => '아이콘을 삭제했습니다.',
        'delete-in-use'  => '{1} 이 아이콘은 엔터티 유형 1개에서 사용 중이므로 삭제할 수 없습니다.|[2,*] 이 아이콘은 엔터티 유형 :count개에서 사용 중이므로 삭제할 수 없습니다.',
        'index'          => [
            'title'      => '아이콘',
            'create-btn' => '아이콘 추가',
        ],

        'create' => [
            'title'    => '아이콘 추가',
            'save-btn' => '아이콘 저장',
        ],

        'edit' => [
            'title'    => '아이콘 편집',
            'save-btn' => '아이콘 저장',
        ],

        'form' => [
            'code'      => '코드',
            'name'      => '이름',
            'file'      => '아이콘 파일',
            'file-info' => 'SVG, PNG 또는 WebP, 최대 :size KB. 투명한 배경에 하나의 도형을 사용하세요. 테마 색상이 적용됩니다.',
            'preview'   => '미리보기',
            'usage'     => '{0} 이 아이콘을 사용하는 엔터티 유형이 아직 없습니다.|{1} 엔터티 유형 1개에서 사용 중입니다.|[2,*] 엔터티 유형 :count개에서 사용 중입니다.',
        ],

        'library' => [
            'barcode'    => '바코드',
            'boxes'      => '자재',
            'briefcase'  => '벤더',
            'building_2' => '회사',
            'cpu'        => '자산',
            'factory'    => '공장',
            'file_text'  => '계약',
            'globe'      => '국가',
            'handshake'  => '파트너',
            'landmark'   => '법인',
            'map_pin'    => '위치',
            'network'    => '계층',
            'package'    => '제품',
            'store'      => '매장',
            'tag'        => '카테고리',
            'truck'      => '공급업체',
            'user_round' => '연락처',
            'users'      => '고객',
            'warehouse'  => '창고',
            'wrench'     => '장비',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => '아이콘',
            'code'    => '코드',
            'name'    => '이름',
            'usage'   => '사용처',
            'edit'    => '편집',
            'delete'  => '삭제',
        ],
    ],

    'match-rules' => [
        'tab'            => '매칭',
        'title'          => '매칭 규칙',
        'info'           => '중복을 찾고 들어오는 행을 매칭하기 위해 이 유형의 레코드를 비교하는 방법을 정합니다. 규칙이 없으면 코드와 레이블을 비교합니다.',
        'create-btn'     => '매칭 규칙 추가',
        'create-success' => '매칭 규칙을 만들었습니다.',
        'update-success' => '매칭 규칙을 업데이트했습니다.',
        'delete-success' => '매칭 규칙을 삭제했습니다.',

        'create' => [
            'title' => '매칭 규칙 추가',
        ],

        'edit' => [
            'title' => '매칭 규칙 편집',
        ],

        'form' => [
            'target'            => '비교 대상',
            'select-target'     => '비교할 항목 선택',
            'target-info'       => '레코드 코드, 레이블 또는 이 엔티티 유형의 필드입니다.',
            'comparator'        => '비교 방식',
            'select-comparator' => '비교 방식 선택',
            'comparator-info'   => '두 값을 비교하는 방식입니다. 예: 오타가 있는 이름에는 유사, 금액에는 숫자.',
            'weight'            => '가중치',
            'weight-info'       => '이 규칙이 반영되는 정도로, 0.1부터 100까지입니다. 점수는 들어오는 행에 값이 있는 규칙들의 가중 평균입니다.',
            'tolerance'         => '허용 오차',
            'tolerance-info'    => '두 숫자를 여전히 비슷하다고 보는 최대 차이입니다. 같은 숫자만 매칭하려면 비워 두세요.',
            'days'              => '날짜 범위(일)',
            'days-info'         => '두 날짜를 여전히 비슷하다고 보는 최대 일수 차이입니다. 같은 날짜만 매칭하려면 비워 두세요.',
            'sort-order'        => '정렬 순서',
            'is-blocking'       => '블로킹 키',
            'is-blocking-info'  => '코드와 레이블이 달라 보여도 이 값이 정확히 같은 레코드도 찾습니다. 필드는 검색 가능해야 합니다.',
            'save-btn'          => '매칭 규칙 저장',
        ],

        'targets' => [
            'code'  => '코드',
            'label' => '레이블',
        ],

        'comparators' => [
            'exact'    => '정확히 일치',
            'fuzzy'    => '유사',
            'token'    => '같은 단어',
            'phonetic' => '비슷한 발음',
            'numeric'  => '숫자',
            'date'     => '날짜',
        ],

        'datagrid' => [
            'target'     => '비교 대상',
            'comparator' => '비교 방식',
            'weight'     => '가중치',
            'blocking'   => '블로킹 키',
            'sort-order' => '정렬 순서',
            'yes'        => '예',
            'no'         => '아니요',
            'edit'       => '편집',
            'delete'     => '삭제',
        ],

        'validation' => [
            'blocking-not-searchable' => '코드, 레이블 또는 검색 가능한 필드만 블로킹 키가 될 수 있습니다.',
            'too-many'                => '엔티티 유형에는 매칭 규칙을 최대 :count개까지 둘 수 있습니다.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => '우선순위',
        'title'          => '우선순위 규칙',
        'info'           => '이 유형의 두 레코드를 병합할 때 골든 레코드가 유지할 값을 정합니다. 규칙이 없으면 남는 레코드는 값이 비어 있지 않은 한 자신의 값을 유지합니다.',
        'create-btn'     => '우선순위 규칙 추가',
        'create-success' => '우선순위 규칙을 만들었습니다.',
        'update-success' => '우선순위 규칙을 업데이트했습니다.',
        'delete-success' => '우선순위 규칙을 삭제했습니다.',

        'create' => [
            'title' => '우선순위 규칙 추가',
        ],

        'edit' => [
            'title' => '우선순위 규칙 편집',
        ],

        'form' => [
            'target'            => '적용 대상',
            'select-target'     => '규칙을 적용할 대상을 선택하세요',
            'target-info'       => '필드, 레코드 레이블 또는 자체 규칙이 없는 모든 필드입니다.',
            'strategy'          => '전략',
            'select-strategy'   => '전략을 선택하세요',
            'strategy-info'     => '남길 값을 고르는 방식입니다. 데이터 스튜어드는 병합 화면에서 각 선택을 계속 변경할 수 있습니다.',
            'source-order'      => '소스 순서',
            'select-sources'    => '소스 시스템을 선택하세요',
            'source-order-info' => '가장 신뢰할 수 있는 소스 시스템이 먼저입니다. 빠진 시스템은 각자의 우선순위를 따릅니다. 비워 두면 소스 시스템 우선순위만 사용합니다.',
            'sort-order'        => '정렬 순서',
            'save-btn'          => '우선순위 규칙 저장',
        ],

        'targets' => [
            'default' => '모든 필드의 기본값',
            'label'   => '레이블',
        ],

        'strategies' => [
            'source_priority' => '가장 신뢰할 수 있는 소스',
            'most_recent'     => '가장 최근 업데이트',
            'most_complete'   => '가장 완전함',
            'keep_target'     => '남는 레코드 유지',
            'manual'          => '스튜어드가 결정',
        ],

        'datagrid' => [
            'target'     => '적용 대상',
            'strategy'   => '전략',
            'sort-order' => '정렬 순서',
            'edit'       => '편집',
            'delete'     => '삭제',
        ],

        'validation' => [
            'target-taken' => '이 엔티티 유형에는 이 대상에 대한 우선순위 규칙이 이미 있습니다.',
            'too-many'     => '엔티티 유형에는 우선순위 규칙을 최대 :count개까지 둘 수 있습니다.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => '하위 :type 둘러보기',
    ],

    'records' => [
        'index' => [
            'top-level' => '최상위',
        ],

        'datagrid' => [
            'erase'         => '영구 삭제',
            'parent'        => '상위 항목',
            'top-level'     => '최상위',
            'children'      => '하위 항목',
            'view-children' => '이 항목 아래의 레코드 :count개 보기',
        ],

        'erase' => [
            'btn'             => '영구 삭제',
            'confirm-title'   => '이 레코드를 영구적으로 삭제하시겠습니까?',
            'confirm-message' => '이 레코드와 여기에 병합된 중복 레코드의 모든 값, 레이블, 코드가 이력, 변경 요청, 품질 이슈와 함께 제거됩니다. 소스 ID와 링크가 계속 연결되도록 레코드는 삭제된 자리 표시자로 남습니다. 이 작업은 되돌릴 수 없습니다.',
            'success'         => '레코드가 영구적으로 삭제되었습니다.',
            'not-retired'     => '폐기된 레코드만 삭제할 수 있습니다. 먼저 폐기하세요.',
        ],

        'mass-retire' => [
            'success' => '레코드 :count개를 폐기했습니다.',
            'pending' => '폐기 :count건을 승인 요청했습니다.',
            'skipped' => '초안 또는 활성 상태가 아니거나 다른 사용자가 변경하여 레코드 :count개를 건너뛰었습니다.',
            'none'    => '선택한 레코드를 하나도 폐기할 수 없습니다.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => '승인 필요: 변경 요청 #:id',
            'description'           => ':requester 님이 :type 에 대한 ":operation" 변경의 승인을 요청했습니다.',
            'description-automated' => ':type 에 대한 자동 ":operation" 변경이 승인을 기다리고 있습니다.',
        ],
    ],

    'access' => [
        'menu'           => '데이터 액세스',
        'acl'            => [
            'index'  => '데이터 액세스',
            'create' => '만들기',
            'edit'   => '편집',
            'delete' => '삭제',
        ],
        'principals' => [
            'role' => '역할',
            'user' => '사용자',
        ],
        'actions' => [
            'view'    => '보기',
            'edit'    => '편집',
            'approve' => '승인',
        ],
        'index' => [
            'title'      => '데이터 액세스',
            'create-btn' => '권한 추가',
            'info'       => '권한은 각 역할 또는 사용자가 접근할 수 있는 범위 지정 엔터티 유형의 레코드를 결정합니다. 선택한 레코드와 그 아래의 모든 항목이 해당됩니다. 사용자의 역할에서 오는 접근 권한과 사용자에게 직접 부여된 권한은 합산됩니다.',
        ],
        'create' => [
            'title' => '데이터 액세스 권한 추가',
        ],
        'edit' => [
            'title' => '데이터 액세스 권한 편집',
        ],
        'form' => [
            'save-btn'         => '권한 저장',
            'principal'        => '부여 대상',
            'principal-info'   => '역할 권한은 해당 역할을 가진 모든 사용자에게 적용됩니다. 예외가 필요하면 개별 사용자에게 부여하세요.',
            'principal-type'   => '부여 대상 유형',
            'role'             => '역할',
            'user'             => '사용자',
            'scope'            => '범위',
            'scope-info'       => '이 권한은 선택한 레코드와 계층 구조에서 그 아래에 있는 모든 레코드에 적용됩니다.',
            'root'             => '하위 트리 루트',
            'root-info'        => '예: 사이트 또는 공장. 그 아래 레코드는 권한을 상속합니다.',
            'entity-type'      => '엔터티 유형',
            'entity-type-info' => '권한을 범위 지정 엔터티 유형 하나로 제한하거나, 비워 두면 모든 범위 지정 유형에 적용됩니다.',
            'any-entity-type'  => '모든 범위 지정 엔터티 유형',
            'actions'          => '작업',
            'actions-info'     => '보기는 레코드를 읽고, 편집은 루트 아래 레코드를 변경·폐기·병합·생성하며, 승인은 보류된 변경을 결정합니다. 편집과 승인에는 보기가 포함됩니다.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => '유형',
            'principal'         => '부여 대상',
            'missing-principal' => '삭제됨',
            'entity-type'       => '엔터티 유형',
            'root'              => '하위 트리 루트',
            'actions'           => '작업',
            'edit'              => '편집',
            'delete'            => '삭제',
        ],
        'role-card' => [
            'title'  => '마스터 데이터 액세스',
            'info'   => '이 역할의 사용자가 접근할 수 있는 범위 지정 엔터티 유형의 레코드입니다. 각 권한은 루트 레코드와 그 아래의 모든 항목에 적용됩니다.',
            'empty'  => '이 역할에는 아직 마스터 데이터 액세스 권한이 없습니다.',
            'remove' => '제거',
            'add'    => '권한 추가',
        ],
        'entity-type' => [
            'is-scoped'      => '하위 트리별 접근 제한',
            'is-scoped-info' => '켜면 사용자는 데이터 액세스 화면에서 자신의 역할 또는 본인에게 부여된 하위 트리 안의 이 유형 레코드에만 접근할 수 있습니다. 공유 참조 데이터는 꺼 두세요.',
        ],
        'validation' => [
            'record-not-found'    => '하위 트리 루트로 볼 수 있는 레코드를 선택하세요.',
            'parent-out-of-scope' => '편집 권한이 있는 하위 트리 안에서 상위 항목을 선택하세요.',
        ],
        'create-success' => '데이터 액세스 권한이 추가되었습니다.',
        'update-success' => '데이터 액세스 권한이 업데이트되었습니다.',
        'delete-success' => '데이터 액세스 권한이 삭제되었습니다.',
    ],

    'install' => [
        'migrating' => '마스터 데이터 마이그레이션을 실행하는 중입니다.',
        'demo'      => '제조 데모 데이터를 설치하는 중입니다.',
        'done'      => '마스터 데이터 관리가 설치되었습니다. 활성 모듈: :modules.',
    ],
];
