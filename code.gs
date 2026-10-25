/**
 * CampusMarket - Backend Controller & Google Apps Script API (Single Admin Store Edition)
 * System Architect: ZettBOT by Zettbos
 * 
 * Compliant with:
 * - Index-Based Mapping (COL Constants)
 * - LockService Concurrency Protection
 * - Incremental Unique ID Generators
 * - Zero Underscore Sheet Naming
 */

// Index-Based Mapping Constants
const COL_SETTINGS = {
  NAMA_TOKO: 0,
  WHATSAPP: 1,
  PASSWORD: 2,
  STATUS: 3,
  LOKASI: 4
};

const COL_MENU = {
  ID: 0,
  NAMA_MENU: 1,
  KATEGORI: 2,
  HARGA: 3,
  FOTO: 4,
  DESKRIPSI: 5,
  TIPE: 6,
  JADWAL: 7,
  DEADLINE_PO: 8,
  STATUS: 9,
  JUMLAH_PESAN: 10,
  STOK: 11,
  CUSTOM_WA: 12
};

/**
 * Handle Web App HTTP GET
 */
function doGet(e) {
  var action = (e && e.parameter && e.parameter.action) || '';

  // Mode API JSON
  if (action === 'getKatalog') {
    return ContentService.createTextOutput(JSON.stringify(getKatalogData()))
      .setMimeType(ContentService.MimeType.JSON);
  }

  // Mode Web App HTML
  var template = HtmlService.createTemplateFromFile('index');
  return template.evaluate()
    .setTitle('CampusMarket - Toko Kuliner Mahasiswa Kampus')
    .addMetaTag('viewport', 'width=device-width, initial-scale=1.0')
    .setXFrameOptionsMode(HtmlService.XFrameOptionsMode.ALLOWALL);
}

/**
 * Ambil data pengaturan toko & daftar menu
 */
function getKatalogData() {
  var ss = SpreadsheetApp.getActiveSpreadsheet();
  var sheetSettings = ss.getSheetByName('PengaturanToko');
  var sheetMenu = ss.getSheetByName('DataMenuMakanan');

  if (!sheetSettings || !sheetMenu) {
    return { success: false, message: 'Sheet belum diinisialisasi. Jalankan setupDatabaseSafeMigrate terlebih dahulu.' };
  }

  var settingsRow = sheetSettings.getRange(2, 1, 1, 5).getValues()[0];
  var settings = {
    namaToko: settingsRow[COL_SETTINGS.NAMA_TOKO],
    nomorWhatsapp: settingsRow[COL_SETTINGS.WHATSAPP],
    statusToko: settingsRow[COL_SETTINGS.STATUS],
    lokasiStandby: settingsRow[COL_SETTINGS.LOKASI]
  };

  var menusRaw = sheetMenu.getDataRange().getValues();
  var menusList = [];
  for (var j = 1; j < menusRaw.length; j++) {
    var mRow = menusRaw[j];
    var menuId = mRow[COL_MENU.ID];
    if (menuId) {
      menusList.push({
        MenuId: mRow[COL_MENU.ID],
        NamaMenu: mRow[COL_MENU.NAMA_MENU],
        Kategori: mRow[COL_MENU.KATEGORI],
        Harga: mRow[COL_MENU.HARGA],
        FotoUrl: mRow[COL_MENU.FOTO],
        Deskripsi: mRow[COL_MENU.DESKRIPSI],
        TipeJualan: mRow[COL_MENU.TIPE],
        JadwalPengantaran: mRow[COL_MENU.JADWAL],
        BatasWaktuPO: mRow[COL_MENU.DEADLINE_PO],
        StatusMenu: mRow[COL_MENU.STATUS],
        JumlahDipesan: mRow[COL_MENU.JUMLAH_PESAN] || 0,
        Stok: mRow[COL_MENU.STOK] !== undefined && mRow[COL_MENU.STOK] !== '' ? Number(mRow[COL_MENU.STOK]) : 15,
        CustomWhatsapp: mRow[COL_MENU.CUSTOM_WA] ? String(mRow[COL_MENU.CUSTOM_WA]) : ''
      });
    }
  }

  return {
    success: true,
    settings: settings,
    menus: menusList
  };
}
