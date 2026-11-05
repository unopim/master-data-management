<?php

return [
    'menu' => [
        'master-data' => 'Dữ liệu chủ',
        'icons'       => 'Biểu tượng',
    ],

    'acl' => [
        'master-data' => 'Dữ liệu chủ',
        'icons'       => [
            'index'  => 'Biểu tượng',
            'create' => 'Tạo',
            'edit'   => 'Chỉnh sửa',
            'delete' => 'Xóa',
        ],

        'records' => [
            'erase' => 'Xóa vĩnh viễn',
        ],
    ],

    'dashboard' => [
        'title'      => 'Dữ liệu chủ',
        'inside'     => 'Thuộc: :types',
        'top-level'  => 'Cấp cao nhất',
        'standalone' => 'Độc lập',
    ],

    'api' => [
        'acl' => [
            'master-data' => 'Dữ liệu chủ',
            'records'     => 'Bản ghi',
            'upsert'      => 'Chèn hoặc cập nhật theo ID hệ thống nguồn',
        ],

        'validation-failed'     => 'Xác thực không thành công.',
        'change-held'           => 'Thay đổi đang chờ phê duyệt.',
        'xref-not-found'        => 'Không có bản ghi nào khớp với ID bên ngoài này.',
        'entity-type-not-found' => 'Không tìm thấy loại thực thể.',
        'record-not-found'      => 'Không tìm thấy bản ghi.',
    ],

    'imports' => [
        'filters' => [
            'entity-type'         => 'Loại thực thể',
            'source-system'       => 'Hệ thống nguồn',
            'code-list'           => 'Danh sách mã',
            'entity-type-info'    => 'Loại thực thể có các bản ghi trong tệp.',
            'source-system-info'  => 'Hệ thống mà tệp đến từ đó. ID bên ngoài của nó được đọc từ cột external_id.',
            'code-list-info'      => 'Danh sách cần nhập vào. Các mã được ghi vào phiên bản nháp của danh sách, bạn sẽ xuất bản riêng.',
            'mapping-info'        => 'Mỗi cột được đổi tên một dòng: cột tệp=mã trường, hoặc mã trường-ngôn ngữ cho trường theo ngôn ngữ, ví dụ Asset Num=external_id và Description=name. Các cột không có dòng được đọc theo tên của chính chúng.',
        ],

        'errors' => [
            'entity-type-invalid'                => 'Chọn một loại thực thể đang hoạt động mà bạn được phép tạo hoặc sửa bản ghi.',
            'source-system-invalid'              => 'Chọn một hệ thống nguồn đang hoạt động.',
            'code-list-invalid'                  => 'Chọn một danh sách mã hiện có mà bạn được phép sửa.',
            'external-id-without-source-system'  => 'Tệp có một cột ID bên ngoài (:column). Hãy chọn hệ thống nguồn mà nó đến từ đó, hoặc xóa cột này.',
        ],

        'schedule' => [
            'job-not-found'  => 'Không có tác vụ nhập nào có mã :code.',
            'not-masterdata' => 'Tác vụ :code không phải là tác vụ nhập dữ liệu chủ.',
            'no-user'        => 'Chỉ định quản trị viên chạy tác vụ nhập bằng --user hoặc MASTERDATA_IMPORT_USER.',
            'user-not-found' => 'Không có quản trị viên đang hoạt động nào có email :email.',
            'file-not-found' => 'Không tìm thấy tệp :path trên ổ đĩa :disk.',
            'nothing'        => 'Không có tệp nào đang chờ trong :path trên ổ đĩa :disk.',
            'queued'         => 'Đã đưa :file vào hàng đợi cho tác vụ :code, theo dõi dưới mã #:id.',
            'imported'       => 'Đã nhập :file cho tác vụ :code, theo dõi dưới mã #:id.',
        ],
    ],

    'demo' => [
        'seeded'           => 'Bản demo sản xuất đã sẵn sàng: :types loại thực thể, :systems hệ thống nguồn, :records bản ghi trong cây địa điểm, :xrefs tham chiếu chéo, :lists danh sách mã, :fields trường, :aliases bí danh.',
        'invalid-template' => 'Không thể đọc mẫu :name hoặc phiên bản định dạng không được hỗ trợ.',
    ],

    'platform-module' => [
        'name' => 'Dữ liệu chủ (MDM)',
    ],

    'entity-types' => [
        'mass-delete-success' => 'Đã xóa loại thực thể: :count.',
        'mass-delete-skipped' => 'Không xóa được vì vẫn còn bản ghi: :codes.',
        'datagrid'            => [
            'allowed-parents'      => 'Loại cha',
            'records-count'        => 'Bản ghi',
            'fields-count'         => 'Trường',
            'updated-at'           => 'Đã cập nhật',
            'schema-version-value' => 'v:version',
            'sort-order'           => 'Thứ tự sắp xếp',
        ],

        'edit' => [
            'schema-version' => 'Lược đồ v:version',
        ],

        'form' => [
            'basics'                    => 'Thông tin cơ bản',
            'identity'                  => 'Định danh',
            'hierarchy'                 => 'Phân cấp',
            'governance'                => 'Quản trị',
            'quality-matching'          => 'Chất lượng và đối sánh',
            'matching-info'             => 'Các dòng đến từ hệ thống nguồn được đối sánh với bản ghi hiện có trong hộp đối sánh.',
            'auto-match-threshold'      => 'Ngưỡng đối sánh tự động',
            'auto-match-off'            => 'Tắt',
            'auto-match-threshold-info' => 'Để trống để xem xét từng dòng. Từ 0,5 đến 1: dòng có gợi ý tốt nhất đạt ít nhất giá trị này, và không có gợi ý nào khác cao bằng, sẽ được chấp nhận tự động. Với các loại cần phê duyệt, liên kết vẫn chờ phê duyệt.',
            'match-on-write'            => 'Kiểm tra trùng lặp khi lưu',
            'match-on-write-info'       => 'Mỗi bản ghi đã lưu được so sánh với các bản ghi khác ở chế độ nền theo quy tắc đối sánh. Tắt để chỉ tìm bản trùng lặp trong các lần quét chất lượng.',
            'manage-icons'              => 'Quản lý biểu tượng',
            'sort-order'                => 'Thứ tự sắp xếp',
            'sort-order-info'           => 'Số nhỏ hơn được hiển thị trước, từ 0 đến 9999; các loại có cùng số được sắp theo mã.',
        ],

        'quick-create' => [
            'code-info'             => 'Chữ thường, chữ số và dấu gạch dưới, bắt đầu bằng một chữ cái. Không thể thay đổi sau khi lưu.',
            'code-pattern-info'     => 'Dùng {seq} hoặc {seq:6} cho số thứ tự và {TYPE} cho mã loại, ví dụ {TYPE}-{seq:6}.',
            'mastering-styles-info' => [
                'consolidation' => 'Bản ghi đến từ nhiều hệ thống nguồn, được đối sánh và hợp nhất thành một bản ghi vàng duy nhất.',
                'centralized'   => 'Bản ghi được tạo và duy trì tại đây, sau đó được xuất bản tới các hệ thống sử dụng chúng.',
            ],
        ],
    ],

    'matching' => [
        'reasons' => [
            'rule' => 'Quy tắc đối sánh trên :target',
        ],

        'show' => [
            'auto-matched' => 'Đối sánh tự động',
            'auto-pending' => 'Đã đối sánh tự động; liên kết đang chờ phê duyệt.',
        ],

        'datagrid' => [
            'decision' => 'Quyết định',
            'auto'     => 'Đối sánh tự động',
            'manual'   => 'Thủ công',
        ],
    ],

    'lineage' => [
        'consumers' => [
            'title'        => 'Được dùng bởi',
            'empty'        => 'Không có tác vụ xuất nào phát hành bản ghi thuộc loại và trạng thái này.',
            'job'          => 'Tác vụ xuất',
            'format'       => 'Định dạng',
            'mode'         => 'Chế độ',
            'full'         => 'Toàn bộ',
            'delta'        => 'Chỉ thay đổi',
            'last-run-at'  => 'Lần chạy hoàn tất gần nhất',
            'no-run'       => 'Chưa từng chạy',
            'record-state' => 'Bản ghi này',
            'states'       => [
                'never_run'  => 'Chưa xuất',
                'pending'    => 'Đã thay đổi từ lần chạy trước',
                'included'   => 'Có trong lần chạy gần nhất',
                'up_to_date' => 'Đã cập nhật',
            ],
        ],
    ],

    'icons' => [
        'create-success' => 'Đã tạo biểu tượng thành công.',
        'update-success' => 'Đã cập nhật biểu tượng thành công.',
        'delete-success' => 'Đã xóa biểu tượng thành công.',
        'delete-in-use'  => '{1} Biểu tượng này đang được một loại thực thể sử dụng nên không thể xóa.|[2,*] Biểu tượng này đang được :count loại thực thể sử dụng nên không thể xóa.',
        'index'          => [
            'title'      => 'Biểu tượng',
            'create-btn' => 'Thêm biểu tượng',
        ],

        'create' => [
            'title'    => 'Thêm biểu tượng',
            'save-btn' => 'Lưu biểu tượng',
        ],

        'edit' => [
            'title'    => 'Chỉnh sửa biểu tượng',
            'save-btn' => 'Lưu biểu tượng',
        ],

        'form' => [
            'code'      => 'Mã',
            'name'      => 'Tên',
            'file'      => 'Tệp biểu tượng',
            'file-info' => 'SVG, PNG hoặc WebP, tối đa :size KB. Dùng một hình duy nhất trên nền trong suốt: hình sẽ lấy màu của giao diện.',
            'preview'   => 'Xem trước',
            'usage'     => '{0} Chưa có loại thực thể nào sử dụng biểu tượng này.|{1} Được một loại thực thể sử dụng.|[2,*] Được :count loại thực thể sử dụng.',
        ],

        'library' => [
            'barcode'    => 'Mã vạch',
            'boxes'      => 'Vật tư',
            'briefcase'  => 'Nhà cung cấp dịch vụ',
            'building_2' => 'Công ty',
            'cpu'        => 'Tài sản',
            'factory'    => 'Nhà máy',
            'file_text'  => 'Hợp đồng',
            'globe'      => 'Quốc gia',
            'handshake'  => 'Đối tác',
            'landmark'   => 'Pháp nhân',
            'map_pin'    => 'Vị trí',
            'network'    => 'Phân cấp',
            'package'    => 'Sản phẩm',
            'store'      => 'Cửa hàng',
            'tag'        => 'Danh mục',
            'truck'      => 'Nhà cung cấp',
            'user_round' => 'Liên hệ',
            'users'      => 'Khách hàng',
            'warehouse'  => 'Kho',
            'wrench'     => 'Thiết bị',
        ],

        'datagrid' => [
            'id'      => 'ID',
            'preview' => 'Biểu tượng',
            'code'    => 'Mã',
            'name'    => 'Tên',
            'usage'   => 'Được dùng bởi',
            'edit'    => 'Chỉnh sửa',
            'delete'  => 'Xóa',
        ],
    ],

    'match-rules' => [
        'tab'            => 'Đối sánh',
        'title'          => 'Quy tắc đối sánh',
        'info'           => 'Quyết định cách so sánh các bản ghi thuộc loại này để tìm bản trùng lặp và đối sánh các dòng đến. Nếu không có quy tắc, mã và nhãn sẽ được so sánh.',
        'create-btn'     => 'Thêm quy tắc đối sánh',
        'create-success' => 'Đã tạo quy tắc đối sánh thành công.',
        'update-success' => 'Đã cập nhật quy tắc đối sánh thành công.',
        'delete-success' => 'Đã xóa quy tắc đối sánh thành công.',

        'create' => [
            'title' => 'Thêm quy tắc đối sánh',
        ],

        'edit' => [
            'title' => 'Chỉnh sửa quy tắc đối sánh',
        ],

        'form' => [
            'target'            => 'So sánh',
            'select-target'     => 'Chọn nội dung cần so sánh',
            'target-info'       => 'Mã bản ghi, nhãn của bản ghi hoặc một trường của loại thực thể này.',
            'comparator'        => 'Phương thức so sánh',
            'select-comparator' => 'Chọn phương thức so sánh',
            'comparator-info'   => 'Cách so sánh hai giá trị, ví dụ Gần đúng cho tên có lỗi chính tả hoặc Số cho số tiền.',
            'weight'            => 'Trọng số',
            'weight-info'       => 'Mức độ ảnh hưởng của quy tắc này, từ 0,1 đến 100. Điểm là trung bình có trọng số của các quy tắc mà dòng đến có giá trị.',
            'tolerance'         => 'Dung sai',
            'tolerance-info'    => 'Chênh lệch lớn nhất giữa hai số mà vẫn được coi là tương tự. Để trống để chỉ đối sánh các số bằng nhau.',
            'days'              => 'Khoảng ngày (ngày)',
            'days-info'         => 'Số ngày tối đa mà hai ngày có thể cách nhau và vẫn được coi là tương tự. Để trống để chỉ đối sánh cùng một ngày.',
            'sort-order'        => 'Thứ tự sắp xếp',
            'is-blocking'       => 'Khóa phân khối',
            'is-blocking-info'  => 'Tìm thêm các bản ghi có cùng chính xác giá trị này, ngay cả khi mã và nhãn trông khác nhau. Các trường phải có thể tìm kiếm.',
            'save-btn'          => 'Lưu quy tắc đối sánh',
        ],

        'targets' => [
            'code'  => 'Mã',
            'label' => 'Nhãn',
        ],

        'comparators' => [
            'exact'    => 'Chính xác',
            'fuzzy'    => 'Gần đúng',
            'token'    => 'Cùng từ ngữ',
            'phonetic' => 'Phát âm giống',
            'numeric'  => 'Số',
            'date'     => 'Ngày',
        ],

        'datagrid' => [
            'target'     => 'So sánh',
            'comparator' => 'Phương thức so sánh',
            'weight'     => 'Trọng số',
            'blocking'   => 'Khóa phân khối',
            'sort-order' => 'Thứ tự sắp xếp',
            'yes'        => 'Có',
            'no'         => 'Không',
            'edit'       => 'Chỉnh sửa',
            'delete'     => 'Xóa',
        ],

        'validation' => [
            'blocking-not-searchable' => 'Chỉ mã, nhãn hoặc trường có thể tìm kiếm mới có thể làm khóa phân khối.',
            'too-many'                => 'Một loại thực thể có thể có tối đa :count quy tắc đối sánh.',
        ],
    ],

    'survivorship-rules' => [
        'tab'            => 'Giá trị ưu tiên',
        'title'          => 'Quy tắc giá trị ưu tiên',
        'info'           => 'Quyết định giá trị mà bản ghi chuẩn giữ lại khi hai bản ghi cùng loại được hợp nhất. Nếu không có quy tắc, bản ghi được giữ lại sẽ giữ nguyên giá trị của nó trừ khi giá trị đó trống.',
        'create-btn'     => 'Thêm quy tắc giá trị ưu tiên',
        'create-success' => 'Đã tạo quy tắc giá trị ưu tiên thành công.',
        'update-success' => 'Đã cập nhật quy tắc giá trị ưu tiên thành công.',
        'delete-success' => 'Đã xóa quy tắc giá trị ưu tiên thành công.',

        'create' => [
            'title' => 'Thêm quy tắc giá trị ưu tiên',
        ],

        'edit' => [
            'title' => 'Chỉnh sửa quy tắc giá trị ưu tiên',
        ],

        'form' => [
            'target'            => 'Áp dụng cho',
            'select-target'     => 'Chọn đối tượng áp dụng của quy tắc',
            'target-info'       => 'Một trường, nhãn của bản ghi hoặc mọi trường chưa có quy tắc riêng.',
            'strategy'          => 'Chiến lược',
            'select-strategy'   => 'Chọn một chiến lược',
            'strategy-info'     => 'Cách chọn giá trị được giữ lại. Người quản lý dữ liệu vẫn có thể thay đổi từng lựa chọn trên màn hình hợp nhất.',
            'source-order'      => 'Thứ tự nguồn',
            'select-sources'    => 'Chọn hệ thống nguồn',
            'source-order-info' => 'Hệ thống nguồn đáng tin cậy nhất đứng đầu. Các hệ thống không được liệt kê theo thứ tự ưu tiên của chúng. Để trống để chỉ dùng mức ưu tiên của hệ thống nguồn.',
            'sort-order'        => 'Thứ tự sắp xếp',
            'save-btn'          => 'Lưu quy tắc giá trị ưu tiên',
        ],

        'targets' => [
            'default' => 'Mặc định cho mọi trường',
            'label'   => 'Nhãn',
        ],

        'strategies' => [
            'source_priority' => 'Nguồn đáng tin cậy nhất',
            'most_recent'     => 'Cập nhật gần đây nhất',
            'most_complete'   => 'Đầy đủ nhất',
            'keep_target'     => 'Giữ bản ghi được giữ lại',
            'manual'          => 'Người quản lý dữ liệu quyết định',
        ],

        'datagrid' => [
            'target'     => 'Áp dụng cho',
            'strategy'   => 'Chiến lược',
            'sort-order' => 'Thứ tự sắp xếp',
            'edit'       => 'Chỉnh sửa',
            'delete'     => 'Xóa',
        ],

        'validation' => [
            'target-taken' => 'Loại thực thể này đã có quy tắc giá trị ưu tiên cho đối tượng này.',
            'too-many'     => 'Một loại thực thể có thể có tối đa :count quy tắc giá trị ưu tiên.',
        ],
    ],

    'hierarchy' => [
        'browse-children' => 'Duyệt :type bên dưới',
    ],

    'records' => [
        'index' => [
            'top-level' => 'Cấp cao nhất',
        ],

        'datagrid' => [
            'erase'         => 'Xóa vĩnh viễn',
            'parent'        => 'Cấp cha',
            'top-level'     => 'Cấp cao nhất',
            'children'      => 'Cấp con',
            'view-children' => 'Hiển thị :count bản ghi bên dưới bản ghi này',
        ],

        'erase' => [
            'btn'             => 'Xóa vĩnh viễn',
            'confirm-title'   => 'Xóa vĩnh viễn bản ghi này?',
            'confirm-message' => 'Tất cả giá trị, nhãn và mã của bản ghi này cùng các bản trùng lặp đã hợp nhất vào nó sẽ bị xóa, kèm theo lịch sử, yêu cầu thay đổi và vấn đề chất lượng. Bản ghi được giữ lại dưới dạng chỗ giữ đã xóa để các ID nguồn và liên kết vẫn hoạt động. Không thể hoàn tác thao tác này.',
            'success'         => 'Đã xóa vĩnh viễn bản ghi.',
            'not-retired'     => 'Chỉ có thể xóa bản ghi đã ngừng sử dụng. Hãy ngừng sử dụng nó trước.',
        ],

        'mass-retire' => [
            'success' => 'Đã ngừng sử dụng :count bản ghi.',
            'pending' => 'Đã gửi :count yêu cầu ngừng sử dụng để phê duyệt.',
            'skipped' => 'Đã bỏ qua :count bản ghi vì không ở trạng thái nháp hoặc hoạt động, hoặc đã bị người khác thay đổi.',
            'none'    => 'Không thể ngừng sử dụng bản ghi nào trong số đã chọn.',
        ],
    ],

    'approvals' => [
        'notification' => [
            'title'                 => 'Cần phê duyệt: yêu cầu thay đổi #:id',
            'description'           => ':requester đã gửi thay đổi ":operation" cho :type để bạn phê duyệt.',
            'description-automated' => 'Một thay đổi tự động ":operation" cho :type đang chờ bạn phê duyệt.',
        ],
    ],

    'access' => [
        'menu'           => 'Quyền truy cập dữ liệu',
        'acl'            => [
            'index'  => 'Quyền truy cập dữ liệu',
            'create' => 'Tạo',
            'edit'   => 'Chỉnh sửa',
            'delete' => 'Xóa',
        ],
        'principals' => [
            'role' => 'Vai trò',
            'user' => 'Người dùng',
        ],
        'actions' => [
            'view'    => 'Xem',
            'edit'    => 'Chỉnh sửa',
            'approve' => 'Phê duyệt',
        ],
        'index' => [
            'title'      => 'Quyền truy cập dữ liệu',
            'create-btn' => 'Thêm quyền',
            'info'       => 'Quyền xác định vai trò hoặc người dùng nào truy cập được những bản ghi của các loại thực thể có phạm vi: bản ghi được chọn và mọi thứ bên dưới nó. Quyền truy cập từ vai trò của người dùng và từ các quyền cấp riêng cho người dùng được cộng gộp.',
        ],
        'create' => [
            'title' => 'Thêm quyền truy cập dữ liệu',
        ],
        'edit' => [
            'title' => 'Chỉnh sửa quyền truy cập dữ liệu',
        ],
        'form' => [
            'save-btn'         => 'Lưu quyền',
            'principal'        => 'Cấp cho',
            'principal-info'   => 'Quyền của vai trò áp dụng cho mọi người dùng có vai trò đó. Hãy cấp cho một người dùng riêng để tạo ngoại lệ.',
            'principal-type'   => 'Cấp cho',
            'role'             => 'Vai trò',
            'user'             => 'Người dùng',
            'scope'            => 'Phạm vi',
            'scope-info'       => 'Quyền bao gồm bản ghi được chọn và mọi bản ghi bên dưới nó trong hệ thống phân cấp.',
            'root'             => 'Gốc của cây con',
            'root-info'        => 'Ví dụ một địa điểm hoặc một nhà máy. Các bản ghi bên dưới kế thừa quyền này.',
            'entity-type'      => 'Loại thực thể',
            'entity-type-info' => 'Giới hạn quyền ở một loại thực thể có phạm vi, hoặc để trống để áp dụng cho mọi loại có phạm vi.',
            'any-entity-type'  => 'Tất cả loại thực thể có phạm vi',
            'actions'          => 'Hành động',
            'actions-info'     => 'Xem để đọc bản ghi, Chỉnh sửa để thay đổi, ngừng sử dụng, hợp nhất và tạo bản ghi bên dưới gốc, Phê duyệt để quyết định các thay đổi đang chờ. Chỉnh sửa và Phê duyệt đã bao gồm Xem.',
        ],
        'datagrid' => [
            'id'                => 'ID',
            'principal-type'    => 'Loại',
            'principal'         => 'Cấp cho',
            'missing-principal' => 'Đã xóa',
            'entity-type'       => 'Loại thực thể',
            'root'              => 'Gốc của cây con',
            'actions'           => 'Hành động',
            'edit'              => 'Chỉnh sửa',
            'delete'            => 'Xóa',
        ],
        'role-card' => [
            'title'  => 'Quyền truy cập dữ liệu chủ',
            'info'   => 'Những bản ghi của các loại thực thể có phạm vi mà người dùng có vai trò này truy cập được. Mỗi quyền bao gồm bản ghi gốc của nó và mọi thứ bên dưới.',
            'empty'  => 'Vai trò này chưa có quyền truy cập dữ liệu chủ nào.',
            'remove' => 'Gỡ bỏ',
            'add'    => 'Thêm quyền',
        ],
        'entity-type' => [
            'is-scoped'      => 'Giới hạn truy cập theo cây con',
            'is-scoped-info' => 'Khi bật, người dùng chỉ truy cập được các bản ghi thuộc loại này trong những cây con được cấp cho vai trò của họ hoặc cho chính họ trên màn hình Quyền truy cập dữ liệu. Hãy tắt đối với dữ liệu tham chiếu dùng chung.',
        ],
        'validation' => [
            'record-not-found'    => 'Hãy chọn một bản ghi mà bạn có thể thấy làm gốc của cây con.',
            'parent-out-of-scope' => 'Hãy chọn bản ghi cha trong cây con mà bạn có quyền chỉnh sửa.',
        ],
        'create-success' => 'Đã thêm quyền truy cập dữ liệu.',
        'update-success' => 'Đã cập nhật quyền truy cập dữ liệu.',
        'delete-success' => 'Đã xóa quyền truy cập dữ liệu.',
    ],
];
