-- Aggiunge supporto login con Google OAuth alla tabella users
-- google_id: ID univoco Google (campo 'sub' da userinfo)
-- avatar: URL foto profilo Google
-- password diventa nullable per utenti registrati solo via Google

ALTER TABLE users
  ADD COLUMN google_id VARCHAR(255) NULL AFTER remember_token,
  ADD COLUMN avatar VARCHAR(500) NULL AFTER google_id,
  MODIFY COLUMN password VARCHAR(255) NULL;

ALTER TABLE users ADD UNIQUE INDEX idx_google_id (google_id);
