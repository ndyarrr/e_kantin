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
    let lastMenungguCount = undefined;

    function playNotificationSound() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            if (!ctx) return;
            
            const playTone = (frequency, startTime, duration) => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                
                osc.type = 'sine';
                osc.frequency.setValueAtTime(frequency, startTime);
                
                gain.gain.setValueAtTime(0.25, startTime);
                gain.gain.exponentialRampToValueAtTime(0.01, startTime + duration);
                
                osc.connect(gain);
                gain.connect(ctx.destination);
                
                osc.start(startTime);
                osc.stop(startTime + duration);
            };
            
            const now = ctx.currentTime;
            playTone(523.25, now, 0.3);     // C5
            playTone(659.25, now + 0.12, 0.4); // E5
        } catch (e) {
            console.error('Gagal memainkan suara notifikasi:', e);
        }
    }

    function showNewOrderToast(count) {
        let container = document.getElementById('newOrderToastContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'newOrderToastContainer';
            container.style.cssText = `
                position: fixed;
                top: 24px;
                right: 24px;
                z-index: 99999999;
                display: flex;
                flex-direction: column;
                gap: 12px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: #ffffff;
            color: #0f172a;
            padding: 16px 20px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.12);
            border: 1.5px solid #e2e8f0;
            border-left: 6px solid #ffb300;
            font-family: 'Poppins', sans-serif;
            display: flex;
            align-items: center;
            gap: 14px;
            opacity: 0;
            transform: translateX(50px);
            transition: all 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: auto;
            cursor: pointer;
            max-width: 320px;
        `;
        
        toast.innerHTML = `
            <div style="width: 40px; height: 40px; background: #fff8e1; color: #ffb300; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                <i class="fa-solid fa-bell"></i>
            </div>
            <div style="display: flex; flex-direction: column; text-align: left;">
                <span style="font-weight: 800; font-size: 13.5px; color: #0f172a;">Ada Pesanan Baru!</span>
                <span style="font-size: 12px; color: #64748b; line-height: 1.4;">Terdapat pesanan baru yang masuk. Silakan periksa tab Menunggu.</span>
            </div>
        `;
        
        toast.addEventListener('click', () => {
            if (typeof window.switchSection === 'function') {
                window.switchSection('inbox');
            }
            setTimeout(() => {
                const tabMenunggu = document.querySelector('.inbox-tab[data-status="menunggu"]');
                if (tabMenunggu) {
                    tabMenunggu.click();
                }
            }, 50);
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(50px)';
            setTimeout(() => toast.remove(), 400);
        });
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.style.opacity = '1';
            toast.style.transform = 'translateX(0)';
        }, 50);
        
        setTimeout(() => {
            if (toast.parentNode) {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(50px)';
                setTimeout(() => toast.remove(), 400);
            }
        }, 6000);
    }


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
        if (isFetching) return;
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

            if (data.jumlahPerStatus && typeof data.jumlahPerStatus.menunggu !== 'undefined') {
                const currentMenunggu = parseInt(data.jumlahPerStatus.menunggu, 10);
                if (lastMenungguCount !== undefined) {
                    if (currentMenunggu > lastMenungguCount) {
                        playNotificationSound();
                        showNewOrderToast(currentMenunggu);
                    }
                }
                lastMenungguCount = currentMenunggu;
            }

            if (isInboxActive()) {
                wrap.innerHTML = data.html;
                filterStatus = data.filterStatus || filterStatus;
                inboxSearch = data.inboxSearch || '';
                cfg.filterStatus = filterStatus;
                cfg.inboxSearch = inboxSearch;
                syncUrl();
                syncPrintedButtons();
            }
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
            if (btn.disabled) return;
            if (typeof window.konfirmasiLunasQrisDirect === 'function') {
                window.konfirmasiLunasQrisDirect(idPesanan);
            } else {
                const confirmMsg = btn.dataset.confirm;
                if (confirmMsg && !confirm(confirmMsg)) return;
                konfirmasiPembayaranQris(idPesanan);
            }
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
            fetchInbox();
        }, cfg.pollInterval || 5000);
    }

    const origSwitch = window.switchSection;
    if (typeof origSwitch === 'function') {
        window.switchSection = function (name) {
            origSwitch(name);
            if (name === 'inbox') {
                fetchInbox();
            }
        };
    }

    updateSearchClearBtn();
    startPolling();
    fetchInbox();

    // Expose fetchInbox globally so external modals (e.g. bukti QRIS) can refresh inbox
    window.muatInbox = fetchInbox;
})();
