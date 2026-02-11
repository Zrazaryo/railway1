// Auto-suggest for Urutan Dokumen (document order)
document.addEventListener('DOMContentLoaded', function() {
    const urutanInput = document.getElementById('document_order_number');
    const monthNumberInput = document.getElementById('month_number');
    if (!urutanInput || !monthNumberInput) return;

    function fetchNextOrder() {
        const monthNumber = monthNumberInput.value;
        if (!monthNumber) return;
        fetch('../api/get_next_order_number.php?month_number=' + encodeURIComponent(monthNumber))
            .then(res => res.json())
            .then(data => {
                if (data && data.next_order) {
                    urutanInput.value = data.next_order;
                }
            });
    }

    // Auto-suggest saat halaman load
    fetchNextOrder();

    // Jika kode lemari berubah (jika ada fitur ganti lemari)
    monthNumberInput.addEventListener('change', fetchNextOrder);
});
