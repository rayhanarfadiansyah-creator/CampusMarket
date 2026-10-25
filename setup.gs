/**
 * CampusMarket - Database Setup & Safe Migration Script (Single Admin Store Edition)
 * System Architect: ZettBOT by Zettbos
 * 
 * Rules:
 * 1. Sheet names MUST NOT contain underscores (PascalCase used).
 * 2. Idempotent & Non-destructive: Does NOT wipe existing user data.
 * 3. Safe Migration: Synchronizes missing columns at the rightmost position.
 * 4. Sample data seeded only if sheet is completely newly created.
 */

const SCHEMA = {
  PengaturanToko: {
    headers: [
      'NamaToko',
      'NomorWhatsapp',
      'AdminPassword',
      'StatusToko',
      'LokasiStandby'
    ],
    sampleData: [
      [
        'CampusMarket Kuliner',
        '081234567890',
        'admin123',
        'Buka',
        'Selasar Kampus Utama / Gedung Perkuliahan'
      ]
    ]
  },

  DataMenuMakanan: {
    headers: [
      'MenuId',
      'NamaMenu',
      'Kategori',
      'Harga',
      'FotoUrl',
      'Deskripsi',
      'TipeJualan',
      'JadwalPengantaran',
      'BatasWaktuPO',
      'StatusMenu',
      'JumlahDipesan',
      'Stok',
      'CustomWhatsapp'
    ],
    sampleData: [
      [
        'MNU-202610-001',
        'Rice Bowl Ayam Geprek Sambal Matah',
        'Makanan Berat',
        15000,
        'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&w=600&q=80',
        'Ayam fillet krispi juicy sambal matah khas Bali, nasi pulen hangat, dan timun segar.',
        'ReadyStock',
        'Ready santap makan siang (11:30 - 13:30 WIB)',
        '',
        'Tersedia',
        48,
        15,
        ''
      ],
      [
        'MNU-202610-002',
        'Risol Mayo Beef Melt Extra Keju (Isi 4)',
        'Camilan',
        14000,
        'https://images.unsplash.com/photo-1606491956689-2ea866880c84?auto=format&fit=crop&w=600&q=80',
        'Kulit risol renyah berpadu smoked beef premium, telur rebus, dan saus mayo gurih lumer melimpah.',
        'OpenPO',
        'Open PO antar serentak jam 13:00 WIB',
        'Order ditutup hari H jam 10:00 pagi',
        'Tersedia',
        76,
        20,
        '085712345678'
      ],
      [
        'MNU-202610-003',
        'Matcha Latte Creamy Uji Pure',
        'Minuman',
        13000,
        'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&w=600&q=80',
        'Matcha murni dipadu fresh milk dingin dan simple syrup gula tebu.',
        'ReadyStock',
        'Ready jam kuliah 10:00 - 15:00 WIB',
        '',
        'Tersedia',
        53,
        12,
        ''
      ]
    ]
  }
};

/**
 * Jalankan fungsi ini di Google Apps Script editor untuk inisialisasi / migrasi spreadsheet secara aman.
 */
function setupDatabaseSafeMigrate() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  
  Object.keys(SCHEMA).forEach(sheetName => {
    let sheet = ss.getSheetByName(sheetName);
    const targetHeaders = SCHEMA[sheetName].headers;
    const sampleRows = SCHEMA[sheetName].sampleData;

    if (!sheet) {
      // 1. Sheet baru: Buat tab baru
      sheet = ss.insertSheet(sheetName);
      
      // Pasang Header
      sheet.getRange(1, 1, 1, targetHeaders.length).setValues([targetHeaders]);
      sheet.getRange(1, 1, 1, targetHeaders.length)
        .setFontWeight('bold')
        .setBackground('#0F172A')
        .setFontColor('#FFFFFF');
      sheet.setFrozenRows(1);

      // Pasang Sample Dummy Rows
      if (sampleRows && sampleRows.length > 0) {
        sheet.getRange(2, 1, sampleRows.length, targetHeaders.length).setValues(sampleRows);
      }
      Logger.log('Berhasil membuat sheet baru: ' + sheetName);
    } else {
      // 2. Sheet sudah ada: Safe Migration tanpa merusak data lama
      const lastCol = Math.max(sheet.getLastColumn(), 1);
      const existingHeaders = sheet.getRange(1, 1, 1, lastCol).getValues()[0];

      targetHeaders.forEach(header => {
        if (!existingHeaders.includes(header)) {
          // Tambahkan kolom baru di posisi paling kanan
          const newColIndex = sheet.getLastColumn() + 1;
          const headerCell = sheet.getRange(1, newColIndex);
          headerCell.setValue(header);
          headerCell.setFontWeight('bold').setBackground('#FF5722').setFontColor('#FFFFFF');
          Logger.log('Safe Migrate: Menambahkan kolom baru [' + header + '] pada sheet ' + sheetName);
        }
      });
    }
  });

  Logger.log('Inisialisasi & Safe Migration Database CampusMarket Selesai!');
}
