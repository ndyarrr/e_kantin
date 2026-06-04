(function () {
    const cfg = window.INBOX_RT_CONFIG;
    if (!cfg) return;

    const wrap = document.getElementById('inboxRealtimeWrap');
    const searchInput = document.getElementById('inboxSearchInput');
    const searchClear = document.getElementById('inboxSearchClear');
    if (!wrap || !searchInput) return;

    let filterStatus = cfg.filterStatus || 'semua';
    let inboxSearch = cfg.inboxSearch || '';
    let searchTimer = null;
    let pollTimer = null;
    let isFetching = false;

    function isInboxActive() {
        const section = document.getElementById('section-inbox');
        return section && section.classList.contains('active');
    }

    function updateSearchClearBtn() {
        if (!searchClear) return;
        searchClear.style.display = searchInput.value.trim() !== '' ? 'flex' : 'none';
    }

    function syncUrl() {
        const url = new URL(window.location.href);
        url.searchParams.set('section', 'inbox');
        url.searchParams.set('status_filter', filterStatus);
        if (inboxSearch.trim()) {
            url.searchParams.set('inbox_search', inboxSearch.trim());
        } else {
            url.searchParams.delete('inbox_search');
        }
        history.replaceState(null, '', url.toString());
    }

    function syncPrintedButtons() {
        document.querySelectorAll('.pcard-btn-selesai').forEach(btn => {
            const id = btn.id.replace('btnSelesai-', '');
            if (localStorage.getItem('printed_order_' + id) === 'true') {
                btn.disabled = false;
                btn.classList.remove('pcard-btn-selesai-locked');
                btn.title = '';
            }
        });
    }

    async function fetchInbox() {
        if (!isInboxActive() || isFetching) return;
        isFetching = true;

        const params = new URLSearchParams({
            status_filter: filterStatus,
            inbox_search: inboxSearch.trim()
        });

        try {
            const res = await fetch(`${cfg.apiUrl}?${params.toString()}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (!data.success) return;

            wrap.innerHTML = data.html;
            filterStatus = data.filterStatus || filterStatus;
            inboxSearch = data.inboxSearch || '';
            cfg.filterStatus = filterStatus;
            cfg.inboxSearch = inboxSearch;
            syncUrl();
            syncPrintedButtons();
        } catch (err) {
            console.error('Gagal memuat inbox:', err);
        } finally {
            isFetching = false;
        }
    }

    function scheduleSearch() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            inboxSearch = searchInput.value;
            updateSearchClearBtn();
            fetchInbox();
        }, 300);
    }

    async function updateStatus(idPesanan, statusBaru) {
        const body = new FormData();
        body.append('action', 'update_status');
        body.append('id_pesanan', idPesanan);
        body.append('status_baru', statusBaru);
        body.append('ajax', '1');

        try {
            const res = await fetch(cfg.prosesUrl, {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.success) {
                if (statusBaru === 'selesai' || statusBaru === 'dibatalkan') {
                    localStorage.removeItem('printed_order_' + idPesanan);
                }
                await fetchInbox();
            } else if (data.message) {
                alert(data.message);
            }
        } catch (err) {
            alert('Gagal mengubah status pesanan.');
        }
    }

    async function konfirmasiPembayaranQris(idPesanan) {
        const body = new FormData();
        body.append('action', 'konfirmasi_pembayaran_qris');
        body.append('id_pesanan', idPesanan);
        body.append('ajax', '1');

        try {
            const res = await fetch(cfg.prosesUrl, {
                method: 'POST',
                body,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            });
            const data = await res.json();
            if (data.success) {
                await fetchInbox();
            } else if (data.message) {
                alert(data.message);
            }
        } catch (err) {
            alert('Gagal mengonfirmasi pembayaran QRIS.');
        }
    }

    searchInput.addEventListener('input', scheduleSearch);

    if (searchClear) {
        searchClear.addEventListener('click', () => {
            searchInput.value = '';
            inboxSearch = '';
            updateSearchClearBtn();
            fetchInbox();
        });
    }

    wrap.addEventListener('click', (e) => {
        const tab = e.target.closest('.inbox-tab[data-status]');
        if (tab) {
            filterStatus = tab.dataset.status;
            cfg.filterStatus = filterStatus;
            fetchInbox();
            return;
        }

        const btn = e.target.closest('[data-action]');
        if (!btn) return;

        const action = btn.dataset.action;
        const idPesanan = btn.dataset.id;

        if (action === 'cetak_nota') {
            try {
                const notaData = JSON.parse(btn.dataset.nota || '{}');
                bukaNotaModal(notaData, parseInt(idPesanan, 10));
            } catch (err) {
                console.error(err);
            }
            return;
        }

        if (action === 'konfirmasi_pembayaran_qris') {
            const confirmMsg = btn.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) return;
            if (btn.disabled) return;
            konfirmasiPembayaranQris(idPesanan);
            return;
        }

        if (action === 'update_status') {
            const statusBaru = btn.dataset.status;
            if (statusBaru === 'dibatalkan') {
                if (btn.disabled) return;
                const card = btn.closest('.pcard');
                const namaPembeli = card ? card.querySelector('.pcard-nama')?.textContent : 'Pembeli';
                if (typeof window.bukaModalBatal === 'function') {
                    window.bukaModalBatal(idPesanan, namaPembeli);
                } else {
                    const confirmMsg = btn.dataset.confirm;
                    if (confirmMsg && !confirm(confirmMsg)) return;
                    updateStatus(idPesanan, statusBaru);
                }
                return;
            }
            const confirmMsg = btn.dataset.confirm;
            if (confirmMsg && !confirm(confirmMsg)) return;
            if (btn.disabled) return;
            updateStatus(idPesanan, statusBaru);
        }
    });

    function startPolling() {
        if (pollTimer) clearInterval(pollTimer);
        pollTimer = setInterval(() => {
            if (isInboxActive()) fetchInbox();
        }, cfg.pollInterval || 5000);
    }

    const origSwitch = window.switchSection;
    if (typeof origSwitch === 'function') {
        window.switchSection = function (name) {
            origSwitch(name);
            if (name === 'inbox') {
                fetchInbox();
                startPolling();
            }
        };
    }

    updateSearchClearBtn();
    startPolling();

    if (isInboxActive()) {
        fetchInbox();
    }

    // Expose fetchInbox globally so external modals (e.g. bukti QRIS) can refresh inbox
    window.muatInbox = fetchInbox;
})();
