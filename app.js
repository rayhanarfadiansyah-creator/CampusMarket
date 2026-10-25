/**
 * CampusMarket - Single Admin Store Controller & Engine
 * System Architect: ZettBOT by Zettbos
 * 
 * Features:
 * - Single Admin Control: Only the store owner can add/edit/delete products.
 * - Public Buyer Experience: No registration needed for campus students.
 * - Direct WhatsApp Ordering: Generates neat order summary directly to the owner's WhatsApp number.
 * - In-Memory Caching & LocalStorage Persistence.
 */

// ==========================================
// 1. DEFAULT STORE SETTINGS & SEED MENUS
// ==========================================

const DEFAULT_STORE_SETTINGS = {
  namaToko: 'CampusMarket Kuliner',
  nomorWhatsapp: '081234567890', // Dapat diubah sewaktu-waktu di Panel Admin
  adminPassword: 'admin123',     // Password rahasia masuk panel admin
  statusToko: 'Buka',
  lokasiStandby: 'Selasar Kampus Utama / Gedung Perkuliahan'
};

const INITIAL_MENUS = [
  {
    MenuId: 'MNU-202610-001',
    NamaMenu: 'Rice Bowl Ayam Geprek Sambal Matah',
    Kategori: 'Makanan Berat',
    Harga: 15000,
    Stok: 15,
    CustomWhatsapp: '',
    FotoUrl: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Ayam fillet krispi juicy sambal matah khas Bali, nasi pulen hangat, dan timun segar.',
    TipeJualan: 'ReadyStock',
    JadwalPengantaran: 'Ready santap makan siang (11:30 - 13:30 WIB)',
    BatasWaktuPO: '',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 48
  },
  {
    MenuId: 'MNU-202610-002',
    NamaMenu: 'Risol Mayo Beef Melt Extra Keju (Isi 4)',
    Kategori: 'Camilan',
    Harga: 14000,
    Stok: 20,
    CustomWhatsapp: '085712345678',
    FotoUrl: 'https://images.unsplash.com/photo-1606491956689-2ea866880c84?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Kulit risol renyah berpadu smoked beef premium, telur rebus, dan saus mayo gurih lumer melimpah.',
    TipeJualan: 'OpenPO',
    JadwalPengantaran: 'Open PO antar serentak jam 13:00 WIB',
    BatasWaktuPO: 'Order ditutup hari H jam 10:00 pagi',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 76
  },
  {
    MenuId: 'MNU-202610-003',
    NamaMenu: 'Matcha Latte Creamy Uji Pure',
    Kategori: 'Minuman',
    Harga: 13000,
    Stok: 12,
    CustomWhatsapp: '',
    FotoUrl: 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Matcha murni dipadu fresh milk dingin dan simple syrup gula tebu asli. Rasa creamy lembut.',
    TipeJualan: 'ReadyStock',
    JadwalPengantaran: 'Ready jam kuliah 10:00 - 15:00 WIB',
    BatasWaktuPO: '',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 53
  },
  {
    MenuId: 'MNU-202610-004',
    NamaMenu: 'Katsu Curry Bento Box Komplit',
    Kategori: 'Makanan Berat',
    Harga: 18000,
    Stok: 10,
    CustomWhatsapp: '',
    FotoUrl: 'https://images.unsplash.com/photo-1569058242253-92a9c755a0ec?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Chicken katsu tebal dengan saus kari Jepang manis gurih, wortel, kentang empuk, dan nasi hangat.',
    TipeJualan: 'OpenPO',
    JadwalPengantaran: 'PO Makan Siang Selasa & Kamis',
    BatasWaktuPO: 'Tutup order jam 09:00 pagi',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 31
  },
  {
    MenuId: 'MNU-202610-005',
    NamaMenu: 'Fudgy Brownies Shiny Crust Bites',
    Kategori: 'Dessert',
    Harga: 16000,
    Stok: 8,
    CustomWhatsapp: '',
    FotoUrl: 'https://images.unsplash.com/photo-1606313564200-e75d5e30476c?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Brownies cokelat pekat chewy dengan shiny crust renyah, taburan almond panggang dan choco chips.',
    TipeJualan: 'ReadyStock',
    JadwalPengantaran: 'Ready stok terbatas / bisa antar ke kelas',
    BatasWaktuPO: '',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 62
  },
  {
    MenuId: 'MNU-202610-006',
    NamaMenu: 'Es Kopi Susu Aren Racik Khusus',
    Kategori: 'Minuman',
    Harga: 10000,
    Stok: 25,
    CustomWhatsapp: '',
    FotoUrl: 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&w=600&q=80',
    Deskripsi: 'Kopi susu mahasiswa anti-ngantuk tugas, espresso robusta dipadu sirup aren organik dan susu gurih.',
    TipeJualan: 'ReadyStock',
    JadwalPengantaran: 'Ready sepanjang hari kuliah',
    BatasWaktuPO: '',
    StatusMenu: 'Tersedia',
    JumlahDipesan: 94
  }
];

// ==========================================
// 2. STATE STORE & IN-MEMORY CACHE
// ==========================================

const StateStore = {
  settings: DEFAULT_STORE_SETTINGS,
  menus: [],
  isAdminLoggedIn: false,
  filters: {
    keyword: '',
    category: 'Semua',
    status: 'Semua'
  },
  cache: new Map(),

  init() {
    try {
      const storedSettings = localStorage.getItem('cm_store_settings');
      const storedMenus = localStorage.getItem('cm_menus');
      const adminAuth = sessionStorage.getItem('cm_admin_auth');

      this.settings = storedSettings ? JSON.parse(storedSettings) : DEFAULT_STORE_SETTINGS;
      this.menus = storedMenus ? JSON.parse(storedMenus) : INITIAL_MENUS;
      this.isAdminLoggedIn = adminAuth === 'true';

      // Ensure backward-compatibility for Stok and CustomWhatsapp
      this.menus.forEach(m => {
        if (m.Stok === undefined || m.Stok === null || isNaN(Number(m.Stok))) {
          m.Stok = 15;
        } else {
          m.Stok = Number(m.Stok);
        }
        if (m.CustomWhatsapp === undefined || m.CustomWhatsapp === null) {
          m.CustomWhatsapp = '';
        }
      });

      if (!storedSettings) this.saveSettings();
      if (!storedMenus) this.saveMenus();
    } catch (e) {
      console.warn('Storage fallback triggered:', e);
      this.settings = DEFAULT_STORE_SETTINGS;
      this.menus = INITIAL_MENUS;
    }
  },

  saveSettings() {
    localStorage.setItem('cm_store_settings', JSON.stringify(this.settings));
    this.cache.clear();
  },

  saveMenus() {
    localStorage.setItem('cm_menus', JSON.stringify(this.menus));
    this.cache.clear();
  },

  setAdminAuth(isLoggedIn) {
    this.isAdminLoggedIn = isLoggedIn;
    if (isLoggedIn) {
      sessionStorage.setItem('cm_admin_auth', 'true');
    } else {
      sessionStorage.removeItem('cm_admin_auth');
    }
  },

  generateUniqueId(prefix, list, idProp) {
    const now = new Date();
    const yearMonth = now.getFullYear().toString() + String(now.getMonth() + 1).padStart(2, '0');
    let maxNum = 0;

    list.forEach(item => {
      const id = item[idProp] || '';
      if (id.startsWith(prefix + '-' + yearMonth + '-')) {
        const parts = id.split('-');
        const num = parseInt(parts[2], 10);
        if (!isNaN(num) && num > maxNum) {
          maxNum = num;
        }
      }
    });

    const nextSeq = String(maxNum + 1).padStart(3, '0');
    return `${prefix}-${yearMonth}-${nextSeq}`;
  },

  generateOrderRef() {
    const now = new Date();
    const ymd = now.getFullYear().toString() +
      String(now.getMonth() + 1).padStart(2, '0') +
      String(now.getDate()).padStart(2, '0');
    const rand = Math.floor(100 + Math.random() * 900);
    return `CM-${ymd}-${rand}`;
  }
};

// ==========================================
// 3. UI CONTROLLER & VIEW RENDERER
// ==========================================

const UI = {
  activeOrderMenu: null,
  activeOrderQty: 1,

  init() {
    StateStore.init();
    this.bindEvents();
    this.renderStoreHeaderInfo();
    this.renderCatalog();
    this.updateAdminButtonUI();
  },

  bindEvents() {
    // Search input
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
      let timeout;
      searchInput.addEventListener('input', (e) => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          StateStore.filters.keyword = e.target.value.trim().toLowerCase();
          UI.renderCatalog();
        }, 200);
      });
    }

    // Category chips
    const categoryChips = document.querySelectorAll('.category-chip');
    categoryChips.forEach(chip => {
      chip.addEventListener('click', () => {
        categoryChips.forEach(c => c.classList.remove('active'));
        chip.classList.add('active');
        StateStore.filters.category = chip.dataset.category;
        UI.renderCatalog();
      });
    });

    // Status toggles
    const statusBtns = document.querySelectorAll('.status-btn');
    statusBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        statusBtns.forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        StateStore.filters.status = btn.dataset.status;
        UI.renderCatalog();
      });
    });

    // Modal click outside & escape
    document.querySelectorAll('.modal-overlay').forEach(modal => {
      modal.addEventListener('click', (e) => {
        if (e.target === modal) {
          UI.closeAllModals();
        }
      });
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        UI.closeAllModals();
      }
    });

    // Header scroll effect
    window.addEventListener('scroll', () => {
      const header = document.querySelector('.site-header');
      if (header) {
        if (window.scrollY > 15) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }
      }
    });
  },

  formatRupiah(num) {
    return 'Rp ' + Number(num).toLocaleString('id-ID');
  },

    normalizeWhatsapp(phone) {
    let clean = String(phone || '').replace(/\D/g, '');

    if (!clean) return '';

    if (clean.startsWith('0')) {
      clean = '62' + clean.slice(1);
    } else if (!clean.startsWith('62')) {
      clean = '62' + clean;
    }

    // Nomor WhatsApp Indonesia harus memiliki panjang yang wajar.
    if (!/^62\d{8,13}$/.test(clean)) {
      return '';
    }

    return clean;
  },

  renderStoreHeaderInfo() {  renderStoreHeaderInfo() {
    const waPill = document.getElementById('headerWaPill');
    const waText = document.getElementById('headerWaText');
    if (waPill && waText) {
      waText.textContent = '💬 Mau Titip Jual? Chat Admin';
      waPill.onclick = () => UI.openTitipJualWhatsapp();
    }
  },

  openTitipJualWhatsapp() {
    const targetWa = this.normalizeWhatsapp(StateStore.settings.nomorWhatsapp || '081234567890');
    const templateMsg = [
      `Halo Admin CampusMarket, saya mahasiswa ingin titip jual produk makanan:`,
      `- Nama Mahasiswa   : [Nama Anda]`,
      `- Fakultas/Jurusan : [Fakultas & Jurusan]`,
      `- Nama Menu Makanan: [Nama Menu]`,
      `- Estimasi Harga   : Rp [Harga]`,
      `- Stok / Porsi     : [Jumlah Porsi]`,
      `- Tipe Penjualan   : [Ready Stock / Open PO]`,
      `Mohon bantu pasang di web CampusMarket ya kak, terima kasih!`
    ].join('\n');

    window.open(`https://wa.me/${targetWa}?text=${encodeURIComponent(templateMsg)}`, '_blank');
  },

  updateAdminButtonUI() {
    const adminBtn = document.getElementById('headerAdminBtn');
    const bottomNavAdmin = document.getElementById('bottomNavAdmin');

    if (StateStore.isAdminLoggedIn) {
      if (adminBtn) {
        adminBtn.innerHTML = `
          <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
          </svg>
          Panel Admin Toko
        `;
      }
      if (bottomNavAdmin) {
        bottomNavAdmin.querySelector('span').textContent = 'Admin Toko';
      }
    } else {
      if (adminBtn) {
        adminBtn.innerHTML = `
          <svg width="15" height="15" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
          </svg>
          Kelola Produk (Admin)
        `;
      }
      if (bottomNavAdmin) {
        bottomNavAdmin.querySelector('span').textContent = 'Admin';
      }
    }
  },

  handleAdminButtonClick() {
    if (StateStore.isAdminLoggedIn) {
      UI.openAdminPanelModal();
    } else {
      UI.openAdminLoginModal();
    }
  },

  // Filter evaluation with In-Memory Caching
  getFilteredMenus() {
    const { keyword, category, status } = StateStore.filters;
    const cacheKey = `${keyword}|${category}|${status}|${StateStore.menus.length}`;

    if (StateStore.cache.has(cacheKey)) {
      return StateStore.cache.get(cacheKey);
    }

    const filtered = StateStore.menus.filter(menu => {
      if (category !== 'Semua' && menu.Kategori !== category) return false;
      if (status === 'ReadyStock' && menu.TipeJualan !== 'ReadyStock') return false;
      if (status === 'OpenPO' && menu.TipeJualan !== 'OpenPO') return false;

      if (keyword) {
        const matchTitle = menu.NamaMenu.toLowerCase().includes(keyword);
        const matchDesc = menu.Deskripsi.toLowerCase().includes(keyword);
        const matchCat = menu.Kategori.toLowerCase().includes(keyword);
        if (!matchTitle && !matchDesc && !matchCat) {
          return false;
        }
      }

      return true;
    });

    StateStore.cache.set(cacheKey, filtered);
    return filtered;
  },

  renderCatalog() {
    const grid = document.getElementById('productsGrid');
    const countEl = document.getElementById('catalogCount');
    if (!grid) return;

    const items = this.getFilteredMenus();
    if (countEl) {
      countEl.textContent = `Menampilkan ${items.length} pilihan kuliner kampus`;
    }

    if (items.length === 0) {
      grid.innerHTML = `
        <div class="empty-state">
          <svg class="empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <h3 class="empty-title">Belum Ada Menu yang Cocok</h3>
          <p class="empty-desc">Coba sesuaikan kata kunci pencarian atau ubah filter kategori Anda.</p>
          <button class="btn btn-outline btn-sm" onclick="UI.resetFilters()">Reset Filter</button>
        </div>
      `;
      return;
    }

    const isStoreOpen = StateStore.settings.statusToko === 'Buka';

    let html = '';
    items.forEach(menu => {
      const isReady = menu.TipeJualan === 'ReadyStock';
      const stock = Number(menu.Stok) !== undefined && !isNaN(Number(menu.Stok)) ? Number(menu.Stok) : 15;
      const isAvailable = menu.StatusMenu === 'Tersedia' && stock > 0;
      const scheduleText = isReady
        ? (menu.JadwalPengantaran || 'Ready santap hari ini')
        : (menu.BatasWaktuPO || 'Pre-Order terjadwal');

      const itemWa = menu.CustomWhatsapp ? menu.CustomWhatsapp : (StateStore.settings.nomorWhatsapp || '081234567890');

      let stockBadgeHtml = '';
      if (!isAvailable || stock <= 0) {
        stockBadgeHtml = `<span class="stock-badge stock-badge-out">❌ Habis</span>`;
      } else if (stock <= 5) {
        stockBadgeHtml = `<span class="stock-badge stock-badge-low">🔥 Sisa ${stock} porsi</span>`;
      } else {
        stockBadgeHtml = `<span class="stock-badge stock-badge-available">⚡ Stok: ${stock} porsi</span>`;
      }

      const titipJualBadge = menu.CustomWhatsapp 
        ? `<div class="badge-titip-jual" title="Produk titip jual mahasiswa langsung ke WA penjual">🤝 Titip Jual</div>` 
        : '';

      html += `
        <article class="food-card" data-menu-id="${menu.MenuId}">
          <div class="food-card-thumb-wrap">
            <img class="food-card-thumb" src="${menu.FotoUrl}" alt="${menu.NamaMenu}" loading="lazy" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';">
            
            <span class="food-type-badge ${isReady ? 'badge-ready' : 'badge-po'}">
              <svg width="12" height="12" fill="currentColor" viewBox="0 0 24 24">
                ${isReady 
                  ? '<path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/>' 
                  : '<path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/>'}
              </svg>
              ${isReady ? 'Ready Stock' : 'Open PO'}
            </span>

            ${stockBadgeHtml}

            <span class="card-wa-indicator" title="Nomor WhatsApp Pesanan Produk">
              <svg width="13" height="13" fill="currentColor" viewBox="0 0 24 24">
                <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/>
              </svg>
              WA: ${itemWa}
            </span>
          </div>

          <div class="food-card-body">
            <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:0.25rem;">
              <span class="food-card-category">${menu.Kategori}</span>
              ${titipJualBadge}
            </div>
            <h3 class="food-card-title">${menu.NamaMenu}</h3>

            <p class="food-card-desc">${menu.Deskripsi}</p>

            <div class="food-card-schedule">
              <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <span>${scheduleText}</span>
            </div>

            <div class="food-card-footer">
              <span class="food-card-price">${UI.formatRupiah(menu.Harga)}</span>
              <div class="food-card-action">
                <button class="btn btn-primary btn-sm" onclick="UI.openOrderModal('${menu.MenuId}')" ${(!isStoreOpen || !isAvailable || stock <= 0) ? 'disabled style="opacity:0.5; cursor:not-allowed;"' : ''}>
                  <svg width="14" height="14" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M17.472 14.382c-.301-.15-1.776-.876-2.051-.976-.275-.1-.475-.15-.675.15-.2.3-.775.976-.95 1.176-.175.2-.35.225-.65.075-.3-.15-1.267-.467-2.414-1.489-.893-.796-1.496-1.78-1.671-2.08-.175-.3-.019-.462.131-.611.136-.134.301-.35.451-.525.15-.175.2-.3.3-.5.1-.2.05-.375-.025-.525-.075-.15-.675-1.625-.925-2.226-.244-.585-.493-.506-.675-.515-.175-.01-.375-.01-.575-.01-.2 0-.525.075-.8.375-.275.3-1.05 1.026-1.05 2.502 0 1.476 1.075 2.899 1.225 3.1.15.2 2.115 3.23 5.124 4.53 3.01 1.3 3.01.867 3.56.812.55-.055 1.775-.726 2.025-1.427.25-.701.25-1.302.175-1.427-.075-.125-.275-.2-.575-.35z"/>
                  </svg>
                  ${!isStoreOpen ? 'Toko Tutup' : (!isAvailable || stock <= 0 ? 'Habis' : 'Pesan via WA')}
                </button>
              </div>
            </div>
          </div>
        </article>
      `;
    });

    grid.innerHTML = html;
  },

  resetFilters() {
    StateStore.filters = { keyword: '', category: 'Semua', status: 'Semua' };
    const searchInput = document.getElementById('searchInput');
    if (searchInput) searchInput.value = '';

    document.querySelectorAll('.category-chip').forEach(c => {
      c.classList.toggle('active', c.dataset.category === 'Semua');
    });
    document.querySelectorAll('.status-btn').forEach(b => {
      b.classList.toggle('active', b.dataset.status === 'Semua');
    });

    this.renderCatalog();
  },

  // ==========================================
  // 4. BUYER ORDER FLOW -> DIRECT TO WA PENJUAL
  // ==========================================

  openOrderModal(menuId) {
    const menu = StateStore.menus.find(m => m.MenuId === menuId);
    if (!menu) return;

    this.activeOrderMenu = menu;
    this.activeOrderQty = 1;

    const targetWaDisplay = menu.CustomWhatsapp 
      ? `${menu.CustomWhatsapp} (Mitra Titip Jual Mahasiswa)` 
      : `${StateStore.settings.nomorWhatsapp || '081234567890'} (Admin Toko)`;

    document.getElementById('orderModalItemTitle').textContent = menu.NamaMenu;
    document.getElementById('orderModalItemPrice').textContent = this.formatRupiah(menu.Harga);
    document.getElementById('orderModalItemCategory').textContent = menu.Kategori;
    document.getElementById('orderModalSellerWa').textContent = targetWaDisplay;
    document.getElementById('orderModalQty').textContent = '1';
    document.getElementById('orderModalTotal').textContent = this.formatRupiah(menu.Harga);

    const modal = document.getElementById('orderModal');
    if (modal) modal.classList.add('active');
  },

  
  adjustOrderQty(delta) {
    if (!this.activeOrderMenu) return;

    const stockValue = Number(this.activeOrderMenu.Stok);
    const maxStock = Number.isFinite(stockValue) && stockValue >= 0
      ? stockValue
      : 0;

    if (maxStock === 0) {
      UI.showToast('Maaf, stok menu ini sudah habis.', 'error');
      return;
    }

    const newQty = this.activeOrderQty + delta;

    if (newQty < 1) return;

    if (newQty > maxStock) {
      UI.showToast(
        `Batas pesanan maksimal: ${maxStock} porsi (sesuai sisa stok)`,
        'info'
      );
      return;
    }

    if (newQty > 50) return;

    this.activeOrderQty = newQty;
    document.getElementById('orderModalQty').textContent = String(newQty);

    const total = this.activeOrderMenu.Harga * newQty;
    document.getElementById('orderModalTotal').textContent =
      this.formatRupiah(total);
  },


  submitOrderToWhatsapp(event) {
    if (event) event.preventDefault();
    if (!this.activeOrderMenu) return;

    const buyerName = document.getElementById('orderBuyerName').value.trim();
    const buyerCampus = document.getElementById('orderBuyerCampus').value.trim();
    const buyerLocation = document.getElementById('orderLocationInput').value.trim();
    const buyerNote = document.getElementById('orderNoteInput').value.trim();

    if (!buyerName || !buyerLocation) {
      UI.showToast('Harap isi Nama Anda dan Titik COD / Antar di Kampus!', 'error');
      return;
    }

    const orderRef = StateStore.generateOrderRef();
    const subtotal = this.activeOrderMenu.Harga * this.activeOrderQty;
    const targetWaNumber = this.activeOrderMenu.CustomWhatsapp || StateStore.settings.nomorWhatsapp;
    const targetWa = UI.normalizeWhatsapp(targetWaNumber);
    if (!targetWa) {
      UI.showToast(
        'Nomor WhatsApp penjual belum valid. Hubungi admin toko.',
        'error'
      );
      return;
    }

    // Official order template directly to the designated WhatsApp
    const messageLines = [
      `Halo kak, saya mau order makanan dari *CampusMarket*:`,
      `-------------------------------------------------`,
      `*No. Pesanan :* ${orderRef}`,
      `*Menu        :* ${this.activeOrderMenu.NamaMenu}`,
      `*Jumlah      :* ${this.activeOrderQty} porsi`,
      `*Total Bayar :* ${UI.formatRupiah(subtotal)}`,
      `-------------------------------------------------`,
      `*Data Pemesan Mahasiswa:*`,
      `- Nama       : ${buyerName}`,
      `- Kampus/Fak : ${buyerCampus || '-'}`,
      `- Titik COD  : ${buyerLocation}`,
      `- Catatan    : ${buyerNote || 'Tidak ada catatan khusus'}`,
      `-------------------------------------------------`,
      `Apakah pesanan ini siap diproses kak? Mohon konfirmasinya ya, terima kasih!`
    ];

    const messageText = messageLines.join('\n');
    const waUrl = `https://wa.me/${targetWa}?text=${encodeURIComponent(messageText)}`;

    // Decrement stock and increment order counter
    const currentStock = Number(this.activeOrderMenu.Stok) || 0;
    const updatedStock = Math.max(0, currentStock - this.activeOrderQty);
    this.activeOrderMenu.Stok = updatedStock;
    if (updatedStock === 0) {
      this.activeOrderMenu.StatusMenu = 'Habis';
    }
    this.activeOrderMenu.JumlahDipesan = (this.activeOrderMenu.JumlahDipesan || 0) + 1;
    StateStore.saveMenus();

    window.open(waUrl, '_blank');

    UI.closeAllModals();
    UI.renderCatalog();
    UI.renderAdminMenuList();
    UI.renderAdminStats();
    UI.showToast('Pesanan berhasil disiapkan! Membuka WhatsApp penjual...', 'success');
  },

  // ==========================================
  // 5. ADMIN AUTHENTICATION & MANAGEMENT
  // ==========================================

  openAdminLoginModal() {
    const modal = document.getElementById('adminLoginModal');
    if (modal) modal.classList.add('active');
  },

  handleAdminLogin(event) {
    event.preventDefault();
    const password = document.getElementById('adminPasswordInput').value.trim();

    if (password === StateStore.settings.adminPassword) {
      StateStore.setAdminAuth(true);
      UI.updateAdminButtonUI();
      UI.closeAllModals();
      UI.showToast('Login Admin Berhasil! Selamat datang pemilik toko.', 'success');
      UI.openAdminPanelModal();
    } else {
      UI.showToast('Password Admin salah!', 'error');
    }
  },

  handleAdminLogout() {
    StateStore.setAdminAuth(false);
    UI.updateAdminButtonUI();
    UI.closeAllModals();
    UI.showToast('Berhasil keluar dari Panel Admin.', 'success');
  },

  openAdminPanelModal() {
    if (!StateStore.isAdminLoggedIn) {
      UI.openAdminLoginModal();
      return;
    }

    // Populate Settings inputs
    document.getElementById('adminStoreNameInput').value = StateStore.settings.namaToko || '';
    document.getElementById('adminStoreWaInput').value = StateStore.settings.nomorWhatsapp || '';
    document.getElementById('adminStorePasswordInput').value = StateStore.settings.adminPassword || '';
    document.getElementById('adminStoreToggle').checked = StateStore.settings.statusToko === 'Buka';

    UI.renderAdminStats();
    UI.renderAdminMenuList();

    const modal = document.getElementById('adminPanelModal');
    if (modal) modal.classList.add('active');
  },

  handleSaveStoreSettings(event) {
    event.preventDefault();
    const name = document.getElementById('adminStoreNameInput').value.trim();
    const wa = document.getElementById('adminStoreWaInput').value.trim();
    const pw = document.getElementById('adminStorePasswordInput').value.trim();

    if (!name || !wa || !pw) {
      UI.showToast('Harap lengkapi semua kolom pengaturan toko!', 'error');
      return;
    }

    StateStore.settings.namaToko = name;
    StateStore.settings.nomorWhatsapp = wa;
    StateStore.settings.adminPassword = pw;
    StateStore.saveSettings();

    UI.renderStoreHeaderInfo();
    UI.renderCatalog();
    UI.showToast('Pengaturan toko & nomor WhatsApp berhasil disimpan!', 'success');
  },

  toggleStoreStatus(checkbox) {
    StateStore.settings.statusToko = checkbox.checked ? 'Buka' : 'Tutup';
    StateStore.saveSettings();
    UI.renderAdminStats();
    UI.renderCatalog();
    UI.showToast(`Status toko sekarang: ${StateStore.settings.statusToko}`, 'success');
  },

  renderAdminStats() {
    const totalMenus = StateStore.menus.length;
    const totalOrders = StateStore.menus.reduce((acc, curr) => acc + (curr.JumlahDipesan || 0), 0);

    document.getElementById('adminStatTotalMenu').textContent = String(totalMenus);
    document.getElementById('adminStatTotalOrders').textContent = String(totalOrders);
    document.getElementById('adminStatStatus').textContent = StateStore.settings.statusToko;
  },

  renderAdminMenuList() {
    const container = document.getElementById('adminMenuList');
    if (!container) return;

    if (StateStore.menus.length === 0) {
      container.innerHTML = `
        <div style="text-align:center; padding:2rem; color:var(--text-muted); font-size:0.88rem;">
          Belum ada produk di dalam toko. Silakan tambahkan produk pertama Anda di atas!
        </div>
      `;
      return;
    }

    let rowsHtml = '';
    StateStore.menus.forEach(menu => {
      const isAvailable = menu.StatusMenu === 'Tersedia' && (Number(menu.Stok) || 0) > 0;
      const stock = Number(menu.Stok) !== undefined && !isNaN(Number(menu.Stok)) ? Number(menu.Stok) : 15;
      const waDisplay = menu.CustomWhatsapp ? `WA Titip: ${menu.CustomWhatsapp}` : 'WA Toko Utama';

      rowsHtml += `
        <tr>
          <td>
            <div style="display:flex; align-items:center; gap:0.6rem;">
              <img src="${menu.FotoUrl}" style="width:42px; height:42px; object-fit:cover; border-radius:6px;" onerror="this.src='https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';">
              <div>
                <strong>${menu.NamaMenu}</strong>
                <div style="font-size:0.72rem; color:var(--text-muted);">${menu.Kategori} • ${menu.TipeJualan}</div>
                <div style="font-size:0.7rem; color:${menu.CustomWhatsapp ? '#1d4ed8' : '#64748b'}; font-weight:600;">${waDisplay}</div>
              </div>
            </div>
          </td>
          <td><strong>${UI.formatRupiah(menu.Harga)}</strong></td>
          <td>
            <div class="stock-stepper-wrap">
              <button type="button" class="btn-stock-step btn-stock-minus" onclick="UI.quickAdjustStock('${menu.MenuId}', -1)" title="Kurangi 1 Porsi">
                -1
              </button>
              <span style="min-width:54px; text-align:center; font-weight:700; font-size:0.8rem; color:${stock === 0 ? '#dc2626' : 'var(--text-main)'};">
                ${stock} porsi
              </span>
              <button type="button" class="btn-stock-step btn-stock-plus" onclick="UI.quickAdjustStock('${menu.MenuId}', 1)" title="Kembalikan / Tambah 1 Porsi (Batal Order WA / Restock)">
                +1
              </button>
            </div>
          </td>
          <td>
            <span style="display:inline-block; padding:0.2rem 0.5rem; border-radius:4px; font-size:0.72rem; font-weight:700; background:${isAvailable ? '#dcfce7' : '#fee2e2'}; color:${isAvailable ? '#15803d' : '#b91c1c'};">
              ${menu.StatusMenu}
            </span>
          </td>
          <td>${menu.JumlahDipesan || 0}x</td>
          <td style="text-align:right;">
            <div style="display:flex; gap:0.35rem; justify-content:flex-end;">
              <button class="btn btn-sm btn-edit" onclick="UI.openEditProductModal('${menu.MenuId}')" title="Edit Produk">
                Edit
              </button>
              <button class="btn btn-outline btn-sm" onclick="UI.toggleMenuAvailability('${menu.MenuId}')" title="Ubah Stok">
                ${isAvailable ? 'Habiskan' : 'Sediakan'}
              </button>
              <button class="btn btn-outline btn-sm" onclick="UI.deleteMenu('${menu.MenuId}')" style="color:#dc2626; border-color:#fecaca;" title="Hapus Produk">
                Hapus
              </button>
            </div>
          </td>
        </tr>
      `;
    });

    container.innerHTML = `
      <div class="seller-menu-table-wrap">
        <table class="seller-table">
          <thead>
            <tr>
              <th>Produk Makanan</th>
              <th>Harga</th>
              <th>Sisa Stok</th>
              <th>Status Stok</th>
              <th>Pesanan WA</th>
              <th style="text-align:right;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            ${rowsHtml}
          </tbody>
        </table>
      </div>
    `;
  },

  handleAddMenu(event) {
    event.preventDefault();
    const nama = document.getElementById('menuFormTitle').value.trim();
    const kategori = document.getElementById('menuFormCategory').value;
    const harga = parseInt(document.getElementById('menuFormPrice').value, 10);
    const stockEl = document.getElementById('menuFormStock');
    const stockVal = stockEl ? parseInt(stockEl.value, 10) : 15;
    const stok = isNaN(stockVal) || stockVal < 0 ? 15 : stockVal;
    const customWaEl = document.getElementById('menuFormCustomWa');
    const customWa = customWaEl ? customWaEl.value.trim() : '';
    const tipe = document.getElementById('menuFormType').value;
    const foto = document.getElementById('menuFormPhoto').value.trim() || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';
    const deskripsi = document.getElementById('menuFormDesc').value.trim();
    const jadwal = document.getElementById('menuFormSchedule').value.trim();

    if (!nama || isNaN(harga) || harga <= 0) {
      UI.showToast('Harap masukkan nama produk dan nominal harga yang valid!', 'error');
      return;
    }

    const newMenuId = StateStore.generateUniqueId('MNU', StateStore.menus, 'MenuId');
    const newMenu = {
      MenuId: newMenuId,
      NamaMenu: nama,
      Kategori: kategori,
      Harga: harga,
      Stok: stok,
      CustomWhatsapp: customWa,
      FotoUrl: foto,
      Deskripsi: deskripsi || 'Menu kuliner pilihan mahasiswa.',
      TipeJualan: tipe,
      JadwalPengantaran: tipe === 'ReadyStock' ? (jadwal || 'Ready jam makan siang') : '',
      BatasWaktuPO: tipe === 'OpenPO' ? (jadwal || 'Pre-order terjadwal') : '',
      StatusMenu: stok === 0 ? 'Habis' : 'Tersedia',
      JumlahDipesan: 0
    };

    StateStore.menus.unshift(newMenu);
    StateStore.saveMenus();

    document.getElementById('addMenuForm').reset();

    UI.renderAdminStats();
    UI.renderAdminMenuList();
    UI.renderCatalog();
    UI.showToast(`Produk "${nama}" berhasil ditambahkan ke web!`, 'success');
  },

  openEditProductModal(menuId) {
    const menu = StateStore.menus.find(m => m.MenuId === menuId);
    if (!menu) return;

    document.getElementById('editMenuId').value = menu.MenuId;
    document.getElementById('editMenuTitle').value = menu.NamaMenu || '';
    document.getElementById('editMenuCategory').value = menu.Kategori || 'Makanan Berat';
    document.getElementById('editMenuPrice').value = menu.Harga || 0;
    document.getElementById('editMenuStock').value = menu.Stok !== undefined ? menu.Stok : 15;
    document.getElementById('editMenuCustomWa').value = menu.CustomWhatsapp || '';
    document.getElementById('editMenuType').value = menu.TipeJualan || 'ReadyStock';
    document.getElementById('editMenuStatus').value = menu.StatusMenu || 'Tersedia';
    document.getElementById('editMenuPhoto').value = menu.FotoUrl || '';
    document.getElementById('editMenuSchedule').value = menu.TipeJualan === 'ReadyStock' 
      ? (menu.JadwalPengantaran || '') 
      : (menu.BatasWaktuPO || '');
    document.getElementById('editMenuDesc').value = menu.Deskripsi || '';

    const modal = document.getElementById('editProductModal');
    if (modal) modal.classList.add('active');
  },

  closeEditProductModal() {
    const modal = document.getElementById('editProductModal');
    if (modal) modal.classList.remove('active');
  },

  handleSaveEditProduct(event) {
    event.preventDefault();
    const menuId = document.getElementById('editMenuId').value;
    const menu = StateStore.menus.find(m => m.MenuId === menuId);
    if (!menu) return;

    const nama = document.getElementById('editMenuTitle').value.trim();
    const kategori = document.getElementById('editMenuCategory').value;
    const harga = parseInt(document.getElementById('editMenuPrice').value, 10);
    const stok = parseInt(document.getElementById('editMenuStock').value, 10);
    const customWa = document.getElementById('editMenuCustomWa').value.trim();
    const tipe = document.getElementById('editMenuType').value;
    const status = document.getElementById('editMenuStatus').value;
    const foto = document.getElementById('editMenuPhoto').value.trim() || 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80';
    const jadwal = document.getElementById('editMenuSchedule').value.trim();
    const deskripsi = document.getElementById('editMenuDesc').value.trim();

    if (!nama || isNaN(harga) || harga <= 0 || isNaN(stok) || stok < 0) {
      UI.showToast('Harap isi Nama, Harga (>0), dan Stok (>=0) dengan benar!', 'error');
      return;
    }

    menu.NamaMenu = nama;
    menu.Kategori = kategori;
    menu.Harga = harga;
    menu.Stok = stok;
    menu.CustomWhatsapp = customWa;
    menu.TipeJualan = tipe;
    menu.StatusMenu = stok === 0 ? 'Habis' : status;
    menu.FotoUrl = foto;
    menu.Deskripsi = deskripsi;

    if (tipe === 'ReadyStock') {
      menu.JadwalPengantaran = jadwal || 'Ready jam makan siang';
      menu.BatasWaktuPO = '';
    } else {
      menu.BatasWaktuPO = jadwal || 'Pre-Order terjadwal';
      menu.JadwalPengantaran = '';
    }

    StateStore.saveMenus();
    UI.closeEditProductModal();
    UI.renderAdminMenuList();
    UI.renderAdminStats();
    UI.renderCatalog();
    UI.showToast(`Produk "${nama}" berhasil diperbarui!`, 'success');
  },

  quickAdjustStock(menuId, delta) {
    const menu = StateStore.menus.find(m => m.MenuId === menuId);
    if (!menu) return;

    const currentStock = Number(menu.Stok) !== undefined && !isNaN(Number(menu.Stok)) ? Number(menu.Stok) : 15;
    const newStock = Math.max(0, currentStock + delta);

    menu.Stok = newStock;
    if (newStock === 0) {
      menu.StatusMenu = 'Habis';
    } else if (menu.StatusMenu === 'Habis' && newStock > 0) {
      menu.StatusMenu = 'Tersedia';
    }

    StateStore.saveMenus();
    UI.renderAdminMenuList();
    UI.renderAdminStats();
    UI.renderCatalog();

    if (delta > 0) {
      UI.showToast(`Porsi "${menu.NamaMenu}" berhasil dipulihkan (+${delta}) jadi ${newStock} porsi!`, 'success');
    } else {
      UI.showToast(`Porsi "${menu.NamaMenu}" dikurangi (-${Math.abs(delta)}) jadi ${newStock} porsi.`, 'info');
    }
  },

  toggleMenuAvailability(menuId) {
    const menu = StateStore.menus.find(m => m.MenuId === menuId);
    if (!menu) return;

    if (menu.StatusMenu === 'Tersedia') {
      menu.StatusMenu = 'Habis';
    } else {
      menu.StatusMenu = 'Tersedia';
      if ((menu.Stok || 0) === 0) {
        menu.Stok = 10;
      }
    }
    StateStore.saveMenus();
    UI.renderAdminMenuList();
    UI.renderCatalog();
    UI.showToast(`Status produk diubah menjadi: ${menu.StatusMenu}`, 'success');
  },

  deleteMenu(menuId) {
    if (!confirm('Apakah Anda yakin ingin menghapus produk ini dari toko?')) return;

    StateStore.menus = StateStore.menus.filter(m => m.MenuId !== menuId);
    StateStore.saveMenus();
    UI.renderAdminStats();
    UI.renderAdminMenuList();
    UI.renderCatalog();
    UI.showToast('Produk berhasil dihapus dari toko.', 'success');
  },

  // ==========================================
  // 6. UTILITIES
  // ==========================================

  closeAllModals() {
    document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('active'));
    this.activeOrderMenu = null;
  },

  showToast(message, type = 'info') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `
      <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        ${type === 'success' 
          ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />' 
          : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />'}
      </svg>
      <span>${message}</span>
    `;

    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-10px)';
      toast.style.transition = 'all 0.3s ease';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  }
};

document.addEventListener('DOMContentLoaded', () => {
  UI.init();
});
