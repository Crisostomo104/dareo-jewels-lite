# VS Code deploy setup (InfinityFree)

1. Install the recommended extension **SFTP** (`liximomo.sftp`), or run
   `ext install liximomo.sftp` from the command palette.
2. Open this project folder in VS Code (the folder that contains `index.php`).
3. Edit `.vscode/sftp.json`:
   - `username` — your InfinityFree FTP username (`if0_...`)
   - `password` — your hosting account password. Leave it out of git.
   - `remotePath` — `/htdocs/lite/` by default. Use `/htdocs` if the
     site is at the domain root instead of a subfolder.
4. **Before the first upload, edit `.env`** with production values:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_URL=https://dareo-jewels.xo.je/lite`
   - `FORCE_HTTPS=true`, `SESSION_SECURE=true`
   - `DB_DRIVER=mysql` plus the MySQL host/name/user/pass from the control panel
     (the MySQL host is like `sqlXXX.infinityfree.com`, **not** `localhost`).
5. Create the MySQL database, import `database/mysql.sql` via phpMyAdmin, then
   run **SFTP: Upload Project** from the Command Palette.
6. After upload, open `https://dareo-jewels.xo.je/lite/.env` — it must return 403.

`uploadOnSave` is intentionally off so local test edits are not pushed by accident.
Use **SFTP: Upload File** for a single file or **SFTP: Upload Project** for all.
