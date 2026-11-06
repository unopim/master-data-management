<?php

return [
    'menu' => [
        'master-data' => 'マスターデータ',
        'icons'       => 'アイコン',
    ],

    'acl' => [
        'master-data' => 'マスターデータ',
        'icons'       => [
            'index'  => 'アイコン',
            'create' => '作成',
            'edit'   => '編集',
            'delete' => '削除',
        ],

        'records' => [
            'erase' => '完全に消去',
        ],
    ],

    'dashboard' => [
        'title'      => 'マスターデータ',
        'inside'     => '所属: :types',
        'top-level'  => '最上位',
        'standalone' => '独立',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'マスターデータ',
            'records'     => 'レコード',
            'upsert'      => 'ソースシステムIDでアップサート',
        ],

        'validation-failed'     => '検証に失敗しました。',
        'change-held'           => 'この変更は承認待ちです。',
        'xref-not-found'        => 'この外部IDに一致するレコードはありません。',
        'entity-type-not-found' => 'エンティティタイプが見つかりません。',
        'record-not-found'      => 'レコードが見つかりません。',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'エンティティタイプ',
            'source-system'       => 'ソースシステム',
            'code-list'           => 'コードリスト',
            'entity-type-info'    => 'ファイルに含まれるレコードのエンティティタイプです。',
            'source-system-info'  => 'ファイルの取得元システムです。外部 ID は external_id 列から読み取られます。',
            'code-list-info'      => 'インポート先のリストです。コードは下書きバージョンに書き込まれ、別途公開します。',
            'mapping-info'        => '名前を変更する列ごとに 1 行: ファイルの列=フィールドコード、ロケール別フィールドの場合はフィールドコード-ロケール(例: Asset Num=external_id、Description=name)。行のない列はその列名のまま読み取られます。',
        ],

        'errors' => [
            'entity-type-invalid'                => 'レコードを作成または編集できる有効なエンティティタイプを選択してください。',
            'source-system-invalid'              => '有効なソースシステムを選択してください。',
            'code-list-invalid'                  => '編集できる既存のコードリストを選択してください。',
            'external-id-without-source-system'  => 'ファイルに外部 ID の列 (:column) があります。取得元のソースシステムを選択するか、列を削除してください。',
        ],

        'schedule' => [
            'job-not-found'  => 'コード :code のインポートジョブはありません。',
            'not-masterdata' => 'ジョブ :code はマスターデータのインポートではありません。',
            'no-user'        => '--user または MASTERDATA_IMPORT_USER で、インポートを実行する管理者を指定してください。',
            'user-not-found' => 'メールアドレス :email の有効な管理者はいません。',
            'file-not-found' => 'ファイル :path がディスク :disk に見つかりませんでした。',
            'nothing'        => 'ディスク :disk の :path に待機中のファイルはありません。',
            'queued'         => 'ジョブ :code の :file をキューに追加しました。追跡ID: #:id。',
            'imported'       => 'ジョブ :code の :file をインポートしました。追跡ID: #:id。',
        ],
    ],

    'demo' => [
        'seeded'           => '製造デモの準備ができました。エンティティタイプ :types 件、ソースシステム :systems 件、サイトツリー内のレコード :records 件、相互参照 :xrefs 件、コードリスト :lists 件、フィールド :fields 件、エイリアス :aliases 件。',
        'invalid-template' => 'テンプレート :name を読み込めないか、フォーマットのバージョンがサポートされていません。',
    ],

    'platform-module' => [
        'name' => 'マスターデータ（MDM）',
    ],

    'entity-types' => [
        'mass-delete-success' => '削除したエンティティタイプ: :count 件。',
        'mass-delete-skipped' => 'レコードが残っているため削除されませんでした: :codes。',
        'datagrid'            => [
            'allowed-parents'      => '親タイプ',
            'records-count'        => 'レコード',
            'fields-count'         => 'フィールド',
            'updated-at'           => '更新日時',
            'schema-version-value' => 'v:version',
            'sort-order'           => '表示順',
        ],

        'edit' => [
            'schema-version' => 'スキーマ v:version',
        ],

        'form' => [
            'basics'                    => '基本情報',
            'identity'                  => '識別',
            'hierarchy'                 => '階層',
            'governance'                => 'ガバナンス',
            'quality-matching'          => '品質とマッチング',
            'matching-info'             => 'ソースシステムからの受信行は、マッチング受信箱で既存のレコードと照合されます。',
            'auto-match-threshold'      => '自動マッチングのしきい値',
            'auto-match-off'            => 'オフ',
            'auto-match-threshold-info' => '空欄にするとすべての行を確認します。0.5〜1：最良の候補がこの値以上で、同じくらい高い候補が他にない行は自動的に承認されます。承認が必要なタイプでは、リンクは引き続き承認待ちになります。',
            'match-on-write'            => '保存時に重複をチェック',
            'match-on-write-info'       => '保存された各レコードは、マッチングルールを使ってバックグラウンドで他のレコードと比較されます。重複を品質スキャンでのみ検出するにはオフにします。',
            'manage-icons'              => 'アイコンを管理',
            'sort-order'                => '表示順',
            'sort-order-info'           => '小さい数字ほど先に表示されます（0〜9999）。同じ数字のタイプはコード順に並びます。',
        ],

        'quick-create' => [
            'code-info'             => '英小文字、数字、アンダースコアを使い、先頭は英字にします。保存後は変更できません。',
            'code-pattern-info'     => '連番には {seq} または {seq:6}、タイプコードには {TYPE} を使用します。例: {TYPE}-{seq:6}',
            'mastering-styles-info' => [
                'consolidation' => 'レコードは複数のソースシステムから届き、照合・統合されて 1 件のゴールデンレコードになります。',
                'centralized'   => 'レコードはここで作成・管理され、利用するシステムへ公開されます。',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => ':target のマッチングルール',
        ],

        'show' => [
            'auto-matched' => '自動マッチング',
            'auto-pending' => '自動的にマッチングされました。リンクは承認待ちです。',
        ],

        'datagrid' => [
            'decision' => '判定',
            'auto'     => '自動マッチング',
            'manual'   => '手動',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => '利用先',
            'empty'        => 'このタイプとステータスのレコードを公開するエクスポートジョブはありません。',
            'job'          => 'エクスポートジョブ',
            'format'       => '形式',
            'mode'         => 'モード',
            'full'         => '全件',
            'delta'        => '変更分のみ',
            'last-run-at'  => '最終完了実行',
            'no-run'       => '未実行',
            'record-state' => 'このレコード',
            'states'       => [
                'never_run'  => '未エクスポート',
                'pending'    => '前回の実行以降に変更',
                'included'   => '前回の実行に含まれています',
                'up_to_date' => '最新',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'アイコンを作成しました。',
        'update-success' => 'アイコンを更新しました。',
        'delete-success' => 'アイコンを削除しました。',
        'delete-in-use'  => '{1} このアイコンは1つのエンティティタイプで使用されているため、削除できません。|[2,*] このアイコンは:count個のエンティティタイプで使用されているため、削除できません。',
        'index'          => [
            'title'      => 'アイコン',
            'create-btn' => 'アイコンを追加',
        ],

        'create' => [
            'title'    => 'アイコンを追加',
            'save-btn' => 'アイコンを保存',
        ],

        'edit' => [
            'title'    => 'アイコンを編集',
            'save-btn' => 'アイコンを保存',
        ],

        'form' => [
            'code'      => 'コード',
            'name'      => '名前',
            'file'      => 'アイコンファイル',
            'file-info' => 'SVG、PNG、WebP形式、:size KBまで。透明な背景に単一の図形を使用してください。テーマの色が適用されます。',
            'preview'   => 'プレビュー',
            'usage'     => '{0} このアイコンを使用しているエンティティタイプはまだありません。|{1} 1つのエンティティタイプで使用中。|[2,*] :count個のエンティティタイプで使用中。',
        ],

        'library' => [
            'barcode'    => 'バーコード',
            'boxes'      => '資材',
            'briefcase'  => 'ベンダー',
            'building_2' => '会社',
            'cpu'        => '資産',
            'factory'    => '工場',
            'file_text'  => '契約',
            'globe'      => '国',
            'handshake'  => 'パートナー',
            'landmark'   => '法人',
            'map_pin'    => '所在地',
            'network'    => '階層',
            'package'    => '製品',
            'store'      => '店舗',
            'tag'        => 'カテゴリ',
            'truck'      => 'サプライヤー',
            'user_round' => '担当者',
            'users'      => '顧客',
            'warehouse'  => '倉庫',
            'wrench'     => '設備',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'アイコン',
            'code'    => 'コード',
            'name'    => '名前',
            'usage'   => '使用先',
            'edit'    => '編集',
            'delete'  => '削除',
        ],
    ],

    'match-rules' => [
        'tab'            => 'マッチング',
        'title'          => 'マッチングルール',
        'info'           => 'このタイプのレコードを比較して重複を見つけ、取り込まれた行を照合する方法を決めます。ルールがない場合は、コードとラベルが比較されます。',
        'create-btn'     => 'マッチングルールを追加',
        'create-success' => 'マッチングルールを作成しました。',
        'update-success' => 'マッチングルールを更新しました。',
        'delete-success' => 'マッチングルールを削除しました。',

        'create' => [
            'title' => 'マッチングルールを追加',
        ],

        'edit' => [
            'title' => 'マッチングルールを編集',
        ],

        'form' => [
            'target'            => '比較対象',
            'select-target'     => '比較する項目を選択',
            'target-info'       => 'レコードのコード、ラベル、またはこのエンティティタイプのフィールド。',
            'comparator'        => '比較方法',
            'select-comparator' => '比較方法を選択',
            'comparator-info'   => '2つの値の比較方法。例：入力ミスのある名前には「あいまい」、金額には「数値」。',
            'weight'            => '重み',
            'weight-info'       => 'このルールの重要度（0.1〜100）。スコアは、取り込まれた行に値があるルールの加重平均です。',
            'tolerance'         => '許容差',
            'tolerance-info'    => '類似とみなされる2つの数値の最大の差。空欄にすると、等しい数値のみが一致します。',
            'days'              => '日付の範囲（日数）',
            'days-info'         => '類似とみなされる2つの日付の最大の日数差。空欄にすると、同じ日のみが一致します。',
            'sort-order'        => '表示順',
            'is-blocking'       => 'ブロッキングキー',
            'is-blocking-info'  => 'コードやラベルが異なって見えても、この値が完全に一致するレコードも検索します。フィールドは検索可能である必要があります。',
            'save-btn'          => 'マッチングルールを保存',
        ],

        'targets' => [
            'code'  => 'コード',
            'label' => 'ラベル',
        ],

        'comparators' => [
            'exact'    => '完全一致',
            'fuzzy'    => 'あいまい',
            'token'    => '同じ単語',
            'phonetic' => '発音が似ている',
            'numeric'  => '数値',
            'date'     => '日付',
        ],

        'datagrid' => [
            'target'     => '比較対象',
            'comparator' => '比較方法',
            'weight'     => '重み',
            'blocking'   => 'ブロッキングキー',
            'sort-order' => '表示順',
            'yes'        => 'はい',
            'no'         => 'いいえ',
            'edit'       => '編集',
            'delete'     => '削除',
        ],

        'validation' => [
            'blocking-not-searchable' => 'ブロッキングキーにできるのは、コード、ラベル、または検索可能なフィールドのみです。',
            'too-many'                => '1つのエンティティタイプに設定できるマッチングルールは最大 :count 件です。',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => '優先順位',
        'title'          => '優先順位ルール',
        'info'           => 'このタイプの2つのレコードを統合したとき、ゴールデンレコードが保持する値を決めます。ルールがない場合、残るレコードは値が空でない限りその値を保持します。',
        'create-btn'     => '優先順位ルールを追加',
        'create-success' => '優先順位ルールを作成しました。',
        'update-success' => '優先順位ルールを更新しました。',
        'delete-success' => '優先順位ルールを削除しました。',

        'create' => [
            'title' => '優先順位ルールを追加',
        ],

        'edit' => [
            'title' => '優先順位ルールを編集',
        ],

        'form' => [
            'target'            => '適用対象',
            'select-target'     => 'ルールの適用対象を選択',
            'target-info'       => 'フィールド、レコードのラベル、または独自のルールがないすべてのフィールドです。',
            'strategy'          => '戦略',
            'select-strategy'   => '戦略を選択',
            'strategy-info'     => '残す値の選び方です。データスチュワードは統合画面で各選択をいつでも変更できます。',
            'source-order'      => 'ソースの順序',
            'select-sources'    => 'ソースシステムを選択',
            'source-order-info' => '最も信頼できるソースシステムを先頭にします。指定しないシステムは各優先度に従います。空欄にするとソースシステムの優先度のみを使用します。',
            'sort-order'        => '表示順',
            'save-btn'          => '優先順位ルールを保存',
        ],

        'targets' => [
            'default' => 'すべてのフィールドのデフォルト',
            'label'   => 'ラベル',
        ],

        'strategies' => [
            'source_priority' => '最も信頼できるソース',
            'most_recent'     => '最終更新が最新',
            'most_complete'   => '最も完全',
            'keep_target'     => '残るレコードを維持',
            'manual'          => 'スチュワードが決定',
        ],

        'datagrid' => [
            'target'     => '適用対象',
            'strategy'   => '戦略',
            'sort-order' => '表示順',
            'edit'       => '編集',
            'delete'     => '削除',
        ],

        'validation' => [
            'target-taken' => 'このエンティティタイプには、この対象の優先順位ルールがすでにあります。',
            'too-many'     => '1つのエンティティタイプに設定できる優先順位ルールは最大 :count 件です。',
        ],
    ],

    'validation-rules' => [
        'tab'            => 'バリデーション',
        'title'          => 'バリデーションルール',
        'info'           => '型だけでなくフィールドの値を検証します。ブロックルールは保存、インポート、API書き込みを拒否し、警告ルールはそれらを通したうえでデータ品質の警告を出します。',
        'create-btn'     => 'バリデーションルールを追加',
        'create-success' => 'バリデーションルールを作成しました。',
        'update-success' => 'バリデーションルールを更新しました。',
        'delete-success' => 'バリデーションルールを削除しました。',

        'create' => [
            'title' => 'バリデーションルールを追加',
        ],

        'edit' => [
            'title' => 'バリデーションルールを編集',
        ],

        'form' => [
            'field'            => 'フィールド',
            'select-field'     => 'フィールドを選択',
            'type'             => 'ルール',
            'select-type'      => 'ルールを選択',
            'severity'         => '重大度',
            'severity-info'    => '「ブロック」はルールに違反する値を拒否します。「警告」は値を保存し、品質警告を報告します。',
            'pattern'          => 'パターン',
            'pattern-info'     => '区切り文字付きの正規表現です。例: /^[A-Z]{2}[0-9]+$/',
            'value'            => '値',
            'min-length'       => '最小文字数',
            'max-length'       => '最大文字数',
            'operator'         => '比較',
            'other-field'      => '比較対象',
            'other-field-info' => 'もう一方のフィールドが空の間は、ルールを満たしたものとして扱われます。',
            'codes'            => '許可されるコード',
            'codes-info'       => '1行に1つ、またはカンマ区切りで入力します。値は完全一致で比較されます。',
            'after'            => 'この日付より前は不可',
            'before'           => 'この日付より後は不可',
            'date-info'        => '2020-01-01 のような日付、または today や -18 years のような相対日付を入力します。どちらかを空にすると、範囲の片側が無制限になります。',
            'messages'         => 'エラーメッセージ',
            'messages-info'    => '値がルールに違反したときに、ユーザーまたはAPIクライアントの言語で表示されます。空にすると既定のメッセージが使われます。:attribute はフィールド名を表します。',
            'sort-order'       => '並び順',
            'save-btn'         => 'バリデーションルールを保存',
        ],

        'types' => [
            'regex'        => 'パターンに一致',
            'min'          => '最小値',
            'max'          => '最大値',
            'length'       => '文字数',
            'cross_field'  => '別のフィールドと比較',
            'one_of_codes' => '指定したコードのいずれか',
            'date_range'   => '日付範囲',
        ],

        'severities' => [
            'block' => 'ブロック',
            'warn'  => '警告',
        ],

        'operators' => [
            'eq'  => 'と等しい',
            'neq' => 'と異なる',
            'gt'  => 'より大きい',
            'gte' => '以上の',
            'lt'  => 'より小さい',
            'lte' => '以下の',
        ],

        'messages' => [
            'default'      => ':attribute は無効です。',
            'regex'        => ':attribute の形式が正しくありません。',
            'min'          => ':attribute は :value 以上である必要があります。',
            'max'          => ':attribute は :value 以下である必要があります。',
            'length'       => ':attribute は :min 文字以上 :max 文字以下である必要があります。',
            'cross_field'  => ':attribute は :field:operator値にする必要があります。',
            'one_of_codes' => ':attribute は次のいずれかである必要があります: :codes',
            'date_range'   => ':attribute は許可された範囲内の日付である必要があります。',
        ],

        'datagrid' => [
            'field'      => 'フィールド',
            'type'       => 'ルール',
            'severity'   => '重大度',
            'sort-order' => '並び順',
            'edit'       => '編集',
            'delete'     => '削除',
        ],

        'validation' => [
            'too-many'     => '1つのエンティティタイプに設定できるバリデーションルールは最大 :count 件です。',
            'invalid-date' => '日付、または today のような相対日付を入力してください。',
        ],
    ],

    'field-transforms' => [
        'title'          => '標準化',
        'info'           => '検証と保存の前に、管理画面、インポート、APIのいずれからでも値をクリーンアップします。フィールドの変換は並び順に実行されます。',
        'create-btn'     => '変換を追加',
        'create-success' => '変換を作成しました。',
        'update-success' => '変換を更新しました。',
        'delete-success' => '変換を削除しました。',

        'create' => [
            'title' => '変換を追加',
        ],

        'edit' => [
            'title' => '変換を編集',
        ],

        'form' => [
            'field'            => 'フィールド',
            'select-field'     => 'フィールドを選択',
            'transform'        => '変換',
            'select-transform' => '変換を選択',
            'transform-info'   => 'テキスト値にのみ適用されます。それ以外の値はそのままになります。',
            'sort-order'       => '並び順',
            'sort-order-info'  => '同じフィールドの変換は、並び順の小さいものから順に実行されます。',
            'save-btn'         => '変換を保存',
        ],

        'types' => [
            'trim'            => '前後の空白を削除',
            'lower'           => '小文字',
            'upper'           => '大文字',
            'title'           => '各単語の先頭を大文字',
            'collapse_spaces' => '連続する空白を1つにまとめる',
            'email_normalize' => 'メールアドレスを正規化',
            'phone_e164'      => '電話番号を正規化 (E.164)',
            'strip_non_alnum' => '英数字のみを残す',
        ],

        'datagrid' => [
            'field'      => 'フィールド',
            'transform'  => '変換',
            'sort-order' => '並び順',
            'edit'       => '編集',
            'delete'     => '削除',
        ],

        'validation' => [
            'taken'    => 'このフィールドには、すでにこの変換が設定されています。',
            'too-many' => '1つのエンティティタイプに設定できる変換は最大 :count 件です。',
        ],
    ],

    'quality' => [
        'rules' => [
            'field_rule' => 'バリデーション警告',
        ],

        'messages' => [
            'field_rule' => ':message',
        ],
    ],

    'hierarchy' => [
        'browse-children' => '下位の :type を表示',
    ],

    'records' => [
        'index' => [
            'top-level' => '最上位',
        ],

        'datagrid' => [
            'erase'         => '完全に消去',
            'parent'        => '親',
            'top-level'     => '最上位',
            'children'      => '子',
            'view-children' => 'この下にある :count 件のレコードを表示',
        ],

        'erase' => [
            'btn'             => '完全に消去',
            'confirm-title'   => 'このレコードを完全に消去しますか？',
            'confirm-message' => 'このレコードと、統合された重複レコードのすべての値、ラベル、コードが、履歴、変更リクエスト、品質問題とともに削除されます。ソース ID とリンクが引き続き解決されるよう、レコードは消去済みのプレースホルダーとして残ります。この操作は元に戻せません。',
            'success'         => 'レコードを完全に消去しました。',
            'not-retired'     => '消去できるのは廃止済みのレコードのみです。先に廃止してください。',
        ],

        'mass-retire' => [
            'success' => ':count 件のレコードを廃止しました。',
            'pending' => ':count 件の廃止を承認のため送信しました。',
            'skipped' => '下書きまたは有効ではない、または他のユーザーが変更したため、:count 件のレコードをスキップしました。',
            'none'    => '選択したレコードはいずれも廃止できませんでした。',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => '承認が必要です: 変更リクエスト #:id',
            'description'           => ':requester が :type への「:operation」変更の承認を依頼しました。',
            'description-automated' => ':type への自動「:operation」変更があなたの承認を待っています。',
        ],
    ],

    'access' => [
        'menu'           => 'データアクセス',
        'acl'            => [
            'index'  => 'データアクセス',
            'create' => '作成',
            'edit'   => '編集',
            'delete' => '削除',
        ],
        'principals' => [
            'role' => 'ロール',
            'user' => 'ユーザー',
        ],
        'actions' => [
            'view'    => '表示',
            'edit'    => '編集',
            'approve' => '承認',
        ],
        'index' => [
            'title'      => 'データアクセス',
            'create-btn' => '権限を追加',
            'info'       => '権限は、各ロールまたはユーザーがアクセスできるスコープ付きエンティティタイプのレコードを決定します。選択したレコードとその配下のすべてが対象です。ユーザーのロールによるアクセスと、ユーザー個人に付与された権限は合算されます。',
        ],
        'create' => [
            'title' => 'データアクセス権限を追加',
        ],
        'edit' => [
            'title' => 'データアクセス権限を編集',
        ],
        'form' => [
            'save-btn'         => '権限を保存',
            'principal'        => '付与先',
            'principal-info'   => 'ロールへの権限は、そのロールを持つすべてのユーザーに適用されます。例外的な場合は個別のユーザーに付与してください。',
            'principal-type'   => '付与先の種類',
            'role'             => 'ロール',
            'user'             => 'ユーザー',
            'scope'            => 'スコープ',
            'scope-info'       => 'この権限は、選択したレコードと階層内のその配下のすべてのレコードに適用されます。',
            'root'             => 'サブツリーのルート',
            'root-info'        => '例：拠点や工場。配下のレコードはこの権限を継承します。',
            'entity-type'      => 'エンティティタイプ',
            'entity-type-info' => '権限を1つのスコープ付きエンティティタイプに限定します。空のままにすると、すべてのスコープ付きタイプが対象です。',
            'any-entity-type'  => 'すべてのスコープ付きエンティティタイプ',
            'actions'          => 'アクション',
            'actions-info'     => '「表示」はレコードの閲覧、「編集」はルート配下のレコードの変更・廃止・統合・作成、「承認」は保留中の変更の可否を決定します。「編集」と「承認」には「表示」が含まれます。',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => '種類',
            'principal'         => '付与先',
            'missing-principal' => '削除済み',
            'entity-type'       => 'エンティティタイプ',
            'root'              => 'サブツリーのルート',
            'actions'           => 'アクション',
            'edit'              => '編集',
            'delete'            => '削除',
        ],
        'role-card' => [
            'title'  => 'マスターデータアクセス',
            'info'   => 'このロールのユーザーがアクセスできるスコープ付きエンティティタイプのレコードです。各権限は、そのルートレコードとその配下のすべてを対象とします。',
            'empty'  => 'このロールにはマスターデータアクセス権限がまだありません。',
            'remove' => '削除',
            'add'    => '権限を追加',
        ],
        'entity-type' => [
            'is-scoped'      => 'サブツリーでアクセスを制限',
            'is-scoped-info' => 'オンにすると、ユーザーは「データアクセス」画面でそのロールまたはユーザー本人に付与されたサブツリー内の、このタイプのレコードにのみアクセスできます。共有の参照データの場合はオフのままにしてください。',
        ],
        'validation' => [
            'record-not-found'    => 'サブツリーのルートとして、表示できるレコードを選択してください。',
            'parent-out-of-scope' => '編集アクセスがあるサブツリー内の親を選択してください。',
        ],
        'create-success' => 'データアクセス権限を追加しました。',
        'update-success' => 'データアクセス権限を更新しました。',
        'delete-success' => 'データアクセス権限を削除しました。',
    ],

    'install' => [
        'migrating' => 'マスターデータのマイグレーションを実行しています。',
        'demo'      => '製造業向けのデモデータをインストールしています。',
        'done'      => 'マスターデータ管理がインストールされました。有効なモジュール: :modules。',
    ],
];
