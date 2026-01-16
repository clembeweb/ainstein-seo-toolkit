# Deploy Guide - Ainstein.it

## SSH
```bash
ssh -i siteground_key -p 18765 u1608-ykgnd3z1twn4@ssh.ainstein.it
```

## DB Produzione
- Host: localhost
- Name: dbj0xoiwysdlk1
- User: u6iaaermphtha

## Path Produzione
```
~/www/ainstein.it/public_html/
```

## Comandi Utili

### Upload file
```bash
scp -i siteground_key -P 18765 file.sql u1608-ykgnd3z1twn4@ssh.ainstein.it:~/www/ainstein.it/public_html/
```

### Import DB
```bash
mysql -u u6iaaermphtha -p dbj0xoiwysdlk1 < database_full_dump.sql
```

### Pull aggiornamenti
```bash
cd ~/www/ainstein.it/public_html
git pull origin main
```

### Permessi
```bash
chmod 755 -R .
chmod 777 -R storage/
```
