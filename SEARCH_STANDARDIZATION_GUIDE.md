# Panduan Standardisasi Fitur Pencarian

## Overview

Semua fitur pencarian di sistem admin dan staff telah distandarisasi menggunakan template dan komponen yang konsisten. Ini memastikan pengalaman pengguna yang seragam di seluruh aplikasi.

## Komponen yang Distandarisasi

### 1. Template Pencarian (`includes/search_template.php`)

Template ini menyediakan dua fungsi utama:
- `render_search_form()` - Form pencarian standar
- `render_advanced_search_modal()` - Modal pencarian lanjutan

### 2. JavaScript Advanced Search (`assets/js/advanced-search.js`)

Class `AdvancedSearch` yang mendukung:
- Pencarian client-side (filter tabel di halaman)
- Pencarian server-side (redirect dengan parameter)
- Auto-initialization berdasarkan halaman

### 3. CSS Styling (`assets/css/search-components.css`)

Styling konsisten untuk semua komponen pencarian.

## Halaman yang Telah Diupdate

### ✅ Staff Dashboard (`staff/dashboard.php`)
- **Fitur**: Pencarian nama, NIK, paspor, kode dokumen
- **Filter**: Kategori (WNA/WNI), Sort (Terbaru/Terlama/Nama A-Z)
- **Advanced Search**: Client-side filtering
- **Tombol**: Refresh, Pencarian Lanjutan

### ✅ Documents Pemusnahan (`documents/pemusnahan.php`)
- **Fitur**: Pencarian nama, NIK, paspor, nomor dokumen
- **Filter**: Kategori, Asal Dokumen, Tahun
- **Advanced Search**: Server-side dengan redirect
- **Tombol**: Refresh, Pencarian Lanjutan

### ✅ Documents Index (`documents/index.php`)
- **Fitur**: Pencarian nama, NIK, paspor, kode dokumen, pembuat
- **Filter**: Kategori, Asal Dokumen, Pembuat
- **Advanced Search**: Modal dengan server-side
- **Tombol**: Refresh, Pencarian Lanjutan

### ✅ Activity Logs (`logs/index.php`)
- **Fitur**: Pencarian nama, username
- **Filter**: Sort (Terbaru/Terlama/Nama)
- **Advanced Search**: Tidak ada (sesuai kebutuhan)
- **Tombol**: Refresh saja

### ✅ Lockers Selection (`lockers/select.php`)
- **Fitur**: Pencarian kode lemari, kode rak
- **Filter**: Sort (berbagai urutan)
- **Advanced Search**: Tidak ada
- **Tombol**: Refresh saja

### ✅ Locker Details (`lockers/detail.php`)
- **Fitur**: Pencarian dokumen dalam rak
- **Filter**: Kategori, Asal Dokumen
- **Advanced Search**: Server-side
- **Tombol**: Refresh, Pencarian Lanjutan

## Fitur Standar yang Diimplementasi

### 1. Form Pencarian Dasar
```php
render_search_form([
    'search_placeholder' => 'Placeholder text...',
    'search_value' => $search,
    'sort_value' => $sort_param,
    'category_value' => $category_filter,
    'sort_options' => [
        'created_at_desc' => 'Dokumen Terbaru',
        'created_at_asc' => 'Dokumen Terlama',
        'name_asc' => 'Nama A-Z'
    ],
    'additional_filters' => [
        // Filter tambahan sesuai kebutuhan
    ]
]);
```

### 2. Modal Pencarian Lanjutan
```php
render_advanced_search_modal([
    'modal_title' => 'Pencarian Lanjutan Dokumen',
    'callback_function' => 'performAdvancedSearch'
]);
```

### 3. JavaScript Integration
```javascript
// Auto-initialization berdasarkan halaman
window.advancedSearchInstance = new AdvancedSearch({
    tableId: 'dataTable',
    searchType: 'client' // atau 'server'
});
```

## Konsistensi UI/UX

### Visual Design
- **Input Group**: Ikon search di sebelah kiri
- **Warna**: Primary blue untuk tombol utama
- **Layout**: Grid responsive Bootstrap
- **Spacing**: Konsisten menggunakan Bootstrap classes

### Interaction Patterns
- **Enter Key**: Submit form di input pencarian
- **Auto Submit**: Dropdown filter otomatis submit
- **Loading States**: Visual feedback saat pencarian
- **Clear Filter**: Tombol untuk reset pencarian

### Accessibility
- **Labels**: Proper form labels
- **ARIA**: Attributes untuk screen readers
- **Keyboard Navigation**: Tab order yang logis
- **Focus States**: Visual focus indicators

## Konfigurasi per Halaman

### Staff Dashboard
- Client-side filtering untuk performa
- Data attributes di table rows
- Real-time filtering tanpa reload

### Documents Pages
- Server-side untuk dataset besar
- URL parameters untuk bookmarking
- Pagination support

### Logs
- Simplified search (nama/username saja)
- Admin-only access
- Performance optimized

### Lockers
- Specialized sorting untuk kode rak
- Numeric + alphabetic ordering
- Capacity management

## Best Practices

### 1. Performance
- Client-side untuk data kecil (<100 rows)
- Server-side untuk data besar (>100 rows)
- Debouncing untuk real-time search

### 2. User Experience
- Placeholder text yang deskriptif
- Loading indicators
- Clear error messages
- Consistent terminology

### 3. Maintenance
- Centralized templates
- Reusable components
- Documented configuration options
- Version control friendly

## Troubleshooting

### Common Issues

1. **Advanced Search tidak berfungsi**
   - Pastikan `advanced-search.js` di-load
   - Check console untuk JavaScript errors
   - Verify modal ID dan form ID

2. **Filter tidak tersimpan**
   - Check URL parameters
   - Verify form method="GET"
   - Ensure proper sanitization

3. **Styling tidak konsisten**
   - Load `search-components.css`
   - Check Bootstrap version compatibility
   - Verify CSS class names

### Debug Mode
```javascript
// Enable debug logging
window.advancedSearchInstance.config.debug = true;
```

## Future Enhancements

### Planned Features
- [ ] Search history/suggestions
- [ ] Export search results
- [ ] Saved search filters
- [ ] Real-time search (websockets)
- [ ] Advanced date range picker
- [ ] Bulk operations on search results

### Technical Improvements
- [ ] Search performance metrics
- [ ] A/B testing framework
- [ ] Progressive Web App features
- [ ] Offline search capability

## Migration Notes

Jika ada halaman lain yang perlu diupdate:

1. Include `search_template.php`
2. Replace existing search form dengan `render_search_form()`
3. Add `advanced-search.js` script
4. Configure search parameters
5. Test functionality

## Support

Untuk pertanyaan atau issues terkait fitur pencarian:
- Check dokumentasi ini terlebih dahulu
- Review kode di `includes/search_template.php`
- Test dengan browser developer tools
- Konsultasi dengan tim development