# TW Backup 365 - Secure & Split WordPress Backup
# TW 備份 365 - 安全分片版

![Version](https://img.shields.io/badge/Version-1.2.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-green.svg)
![License](https://img.shields.io/badge/License-GPLv2-orange.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)

[English](#english) | [繁體中文](#繁體中文)

---

<a name="english"></a>
## 🇺🇸 English

**TW Backup 365** is a lightweight, secure, and robust full-site backup plugin for WordPress. It is designed to handle large websites on shared hosting environments by splitting backup archives and implementing strict security measures based on OWASP Top 10 standards.

### 🌟 Key Features

* **Full Site Backup:** Backs up the entire WordPress root directory and the database (from `wp-config.php`).
* **Smart Splitting (Chunking):** Automatically splits large backup files into **20MB parts** (e.g., `.zip.001`, `.zip.002`). This prevents server timeouts and bypasses upload limits.
* **Security Hardened (v1.2.0+):**
    * **Anti-Brute Force:** Uses 16-byte randomized hex strings in filenames to prevent guessing.
    * **Access Control:** Auto-generates `.htaccess` and `web.config` to block public access to the backup folder.
    * **Path Hiding:** Admin interface hides absolute server paths (`/home/user/...`) to prevent information leakage.
    * **DoS Protection:** Enforces a **10-minute cooldown** between backups to prevent server exhaustion.
* **Memory Optimization:** Uses buffer flushing to keep memory usage low during compression.
* **One-Click Operation:** Simple and intuitive interface.

### 🚀 Installation

1.  Download the plugin `.zip` file.
2.  Go to WordPress Dashboard > **Plugins** > **Add New**.
3.  Click **Upload Plugin**, select the file, and click **Install Now**.
4.  **Activate** the plugin.

### 📖 Usage

1.  Navigate to **Backup 365** in the sidebar.
2.  Click **Start Secure Backup**.
3.  Wait for the process to complete.
4.  Use an **FTP/SFTP client** (like FileZilla) to download the backup files from the secure directory shown on the screen (e.g., `/wp-content/uploads/tw-backup-secure/`).

### 📦 How to Restore

Since the files are split, you need to combine them before restoring:

1.  Download **all parts** (e.g., `...FULL.zip.001`, `...FULL.zip.002`) to your computer.
2.  **Windows:** Use [7-Zip](https://www.7-zip.org/). Right-click the `.001` file and select "Extract Here".
3.  **Mac:** Use [Keka](https://www.keka.io/) or The Unarchiver. Double-click the `.001` file.
4.  The software will automatically detect other parts and extract the full `.zip`.
5.  Upload the extracted files to your server and import the `.sql` file into your database.

### ⚠️ Security Note for Nginx Users

If you are using **Nginx**, `.htaccess` rules will not work.
1.  Please ensure **Directory Listing** is disabled in your Nginx config.
2.  We have implemented **Randomized Filenames** as a secondary defense, making it nearly impossible for attackers to guess the backup URL.

---

<a name="繁體中文"></a>
## 🇹🇼 繁體中文

**TW Backup 365** 是一款輕量、安全且強大的 WordPress 全站備份外掛。專為解決共享主機上的備份難題而設計，具備檔案分片壓縮與符合 OWASP 標準的高安全性防護。

### 🌟 核心功能

* **全站備份：** 完整備份 WordPress 根目錄所有檔案與資料庫。
* **智慧分片 (Chunking)：** 自動將巨大的備份檔切割為 **20MB 的小檔案**（如 `.zip.001`, `.zip.002`），有效解決伺服器記憶體不足與下載超時的問題。
* **資安強化 (v1.2.0+):**
    * **防暴力破解：** 檔名強制加入 16 位元亂數雜湊，駭客無法透過時間猜測下載路徑。
    * **存取控制：** 自動生成 `.htaccess` 與 `web.config`，禁止外部直接存取備份目錄。
    * **路徑隱藏：** 後台介面隱藏伺服器絕對路徑（Absolute Path），防止主機資訊外洩。
    * **防 DoS 攻擊：** 內建 **10 分鐘冷卻時間** 機制，防止惡意連續觸發備份拖垮主機。
* **記憶體優化：** 採用分段寫入技術，大幅降低 PHP 記憶體消耗。
* **一鍵操作：** 介面簡單直覺，支援繁體中文。

### 🚀 安裝方式

1.  下載外掛 `.zip` 檔案。
2.  進入 WordPress 後台 > **外掛** > **安裝外掛**。
3.  點擊 **上傳外掛**，選擇檔案並安裝。
4.  **啟用** 外掛。

### 📖 使用說明

1.  點擊左側選單的 **備份 365 (Backup 365)**。
2.  點擊 **開始安全備份** 按鈕。
3.  等待進度條跑完（或頁面重整）。
4.  基於安全性考量，請使用 **FTP/SFTP 軟體**（如 FileZilla）連線至主機，並從畫面上顯示的目錄（通常為 `/wp-content/uploads/tw-backup-secure/`）下載檔案。

### 📦 還原教學

由於檔案經過分片處理，還原前需要先解壓縮合併：

1.  將 **所有分片檔案**（例如 `...FULL.zip.001`, `...FULL.zip.002`）下載到電腦的同一個資料夾。
2.  **Windows 使用者：** 安裝 [7-Zip](https://www.7-zip.org/)，對著 **`.001`** 檔案按右鍵，選擇「解壓縮至此」。
3.  **Mac 使用者：** 使用 [Keka](https://www.keka.io/) 或 The Unarchiver，直接點擊 **`.001`** 檔案。
4.  解壓縮軟體會自動抓取所有分片並合併還原成完整的資料夾。
5.  將檔案上傳回主機，並將 `.sql` 匯入資料庫即可。

### ⚠️ Nginx 使用者注意事項

如果您使用的是 **Nginx** 伺服器，`.htaccess` 規則將不會生效。
1.  請確保您的 Nginx 設定已關閉 **目錄列表 (Directory Listing)** 功能。
2.  本外掛已實作 **亂數檔名防護** 作為第二層保護，即使沒有目錄保護，攻擊者也極難猜測到下載連結。

---

## 📄 License

Released under the GPLv2 License.
