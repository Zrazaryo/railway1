<?php
/**
 * Template Pencarian Standar
 * Digunakan untuk semua halaman admin dan staff
 */

function render_search_form($config = []) {
    // Default configuration
    $defaults = [
        'search_placeholder' => 'Cari nama, NIK, paspor, kode dokumen atau...',
        'show_category_filter' => true,
        'show_sort_options' => true,
        'show_advanced_search' => true,
        'show_refresh_button' => true,
        'form_id' => 'searchForm',
        'search_value' => $_GET['search'] ?? '',
        'sort_value' => $_GET['sort'] ?? 'created_at_desc',
        'category_value' => $_GET['category'] ?? '',
        'refresh_url' => $_SERVER['PHP_SELF'],
        'additional_filters' => [],
        'sort_options' => [
            'created_at_desc' => 'Dokumen Terbaru',
            'created_at_asc' => 'Dokumen Terlama', 
            'name_asc' => 'Nama A-Z'
        ]
    ];
    
    $config = array_merge($defaults, $config);
    ?>
    
    <!-- Search and Filter Container -->
    <div class="search-filter-container mb-4">
        <form method="GET" id="<?php echo $config['form_id']; ?>" class="row g-3">
            <!-- Search Input -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" class="form-control" name="search" 
                           value="<?php echo htmlspecialchars($config['search_value']); ?>" 
                           placeholder="<?php echo $config['search_placeholder']; ?>"
                           onkeypress="if(event.key === 'Enter') { event.preventDefault(); document.getElementById('<?php echo $config['form_id']; ?>').submit(); }">
                </div>
            </div>
            
            <?php if ($config['show_sort_options']): ?>
            <!-- Sort Options -->
            <div class="col-md-3">
                <select class="form-select" name="sort" onchange="document.getElementById('<?php echo $config['form_id']; ?>').submit();">
                    <?php foreach ($config['sort_options'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo $config['sort_value'] === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            
            <?php if ($config['show_category_filter']): ?>
            <!-- Category Filter -->
            <div class="col-md-2">
                <select class="form-select" name="category" onchange="document.getElementById('<?php echo $config['form_id']; ?>').submit();">
                    <option value="">Semua Kategori</option>
                    <option value="WNA" <?php echo $config['category_value'] === 'WNA' ? 'selected' : ''; ?>>WNA</option>
                    <option value="WNI" <?php echo $config['category_value'] === 'WNI' ? 'selected' : ''; ?>>WNI</option>
                </select>
            </div>
            <?php endif; ?>
            
            <!-- Additional Filters -->
            <?php foreach ($config['additional_filters'] as $filter): ?>
            <div class="col-md-<?php echo $filter['col_size'] ?? '2'; ?>">
                <select class="form-select" name="<?php echo $filter['name']; ?>" onchange="document.getElementById('<?php echo $config['form_id']; ?>').submit();">
                    <option value=""><?php echo $filter['placeholder']; ?></option>
                    <?php foreach ($filter['options'] as $value => $label): ?>
                        <option value="<?php echo $value; ?>" <?php echo ($_GET[$filter['name']] ?? '') === $value ? 'selected' : ''; ?>>
                            <?php echo $label; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
            
            <?php if ($config['show_refresh_button']): ?>
            <!-- Refresh Button -->
            <div class="col-md-1">
                <button type="button" class="btn btn-primary w-100" title="Muat Ulang" onclick="location.href='<?php echo $config['refresh_url']; ?>'">
                    <i class="fas fa-rotate-right"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if ($config['show_advanced_search']): ?>
            <!-- Advanced Search Button -->
            <div class="col-md-2">
                <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 py-2" 
                        data-bs-toggle="modal" data-bs-target="#advancedSearchModal" title="Pencarian Lanjutan">
                    <span class="fw-semibold">Pencarian Lanjutan</span>
                    <i class="fas fa-search-plus fa-lg"></i>
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>
    
    <?php
}

/**
 * Template Advanced Search Modal
 */
function render_advanced_search_modal($config = []) {
    $defaults = [
        'modal_id' => 'advancedSearchModal',
        'modal_title' => 'Pencarian Lanjutan Dokumen',
        'form_id' => 'advancedSearchForm',
        'search_fields' => [
            'full_name' => [
                'label' => 'Nama Lengkap',
                'icon' => 'fas fa-user text-primary',
                'type' => 'text',
                'placeholder' => 'Masukkan nama lengkap...',
                'help' => 'Cari berdasarkan nama lengkap pemilik dokumen'
            ],
            'birth_date' => [
                'label' => 'Tanggal Lahir',
                'icon' => 'fas fa-calendar text-success',
                'type' => 'date',
                'help' => 'Cari berdasarkan tanggal lahir'
            ],
            'passport_number' => [
                'label' => 'Nomor Paspor',
                'icon' => 'fas fa-passport text-info',
                'type' => 'text',
                'placeholder' => 'Masukkan nomor paspor...',
                'help' => 'Cari berdasarkan nomor paspor'
            ]
        ],
        'callback_function' => 'performAdvancedSearch'
    ];
    
    $config = array_merge($defaults, $config);
    ?>
    <!-- Advanced Search Modal -->
    <div class="modal fade" id="<?php echo $config['modal_id']; ?>" tabindex="-1" aria-labelledby="<?php echo $config['modal_id']; ?>Label" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="<?php echo $config['modal_id']; ?>Label">
                        <i class="fas fa-search-plus"></i> <?php echo $config['modal_title']; ?>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="<?php echo $config['form_id']; ?>" onsubmit="return false;">
                        <div class="row g-3">
                            <?php foreach ($config['search_fields'] as $field_name => $field_config): ?>
                            <div class="col-md-6">
                                <label for="search_<?php echo $field_name; ?>" class="form-label">
                                    <i class="<?php echo $field_config['icon']; ?>"></i> <?php echo $field_config['label']; ?>
                                </label>
                                <input type="<?php echo $field_config['type']; ?>" 
                                       class="form-control" 
                                       id="search_<?php echo $field_name; ?>" 
                                       name="<?php echo $field_name; ?>"
                                       <?php if (isset($field_config['placeholder'])): ?>
                                       placeholder="<?php echo $field_config['placeholder']; ?>"
                                       <?php endif; ?>
                                >
                                <?php if (isset($field_config['help'])): ?>
                                <div class="form-text"><?php echo $field_config['help']; ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <!-- Search Tips -->
                        <div class="alert alert-info mt-3">
                            <h6><i class="fas fa-lightbulb"></i> Tips Pencarian:</h6>
                            <ul class="mb-0">
                                <li>Isi satu atau lebih field untuk hasil yang lebih spesifik.</li>
                                <li>Kombinasi beberapa field memberi hasil lebih akurat.</li>
                                <li>Kosongkan field yang tidak ingin dipakai.</li>
                            </ul>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="btnCariDokumen" onclick="try { window['<?php echo $config['callback_function']; ?>'](); } catch(e) { console.error(e); }">
                        <i class="fas fa-search"></i> Cari Dokumen
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    // Event handler untuk tombol Cari Dokumen
    function initializeSearchButton() {
        var btn = document.getElementById('btnCariDokumen');
        if(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                var callbackName = '<?php echo $config['callback_function']; ?>';
                console.log('Tombol Cari Dokumen diklik, callback: ' + callbackName);
                console.log('Fungsi ada?', typeof window[callbackName]);
                if(typeof window[callbackName] === 'function') {
                    console.log('Menjalankan ' + callbackName + '()');
                    window[callbackName]();
                } else {
                    console.error('Fungsi ' + callbackName + ' tidak ditemukan');
                    // List semua fungsi yang tersedia di window
                    console.log('Fungsi yang tersedia:', Object.getOwnPropertyNames(window).filter(p => typeof window[p] === 'function').slice(0, 30));
                }
            });
            console.log('Event listener untuk tombol Cari Dokumen sudah di-set');
        } else {
            console.error('Tombol btnCariDokumen tidak ditemukan di DOM');
        }
    }
    
    // Initialize saat document ready
    if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeSearchButton);
    } else {
        initializeSearchButton();
    }
    </script>
    <?php
}