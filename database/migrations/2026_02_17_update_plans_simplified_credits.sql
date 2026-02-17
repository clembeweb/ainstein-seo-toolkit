-- Migration: Aggiornamento piani per modello crediti semplificati (4 livelli)
-- Data: 2026-02-17
-- Descrizione: Aggiorna i piani con nuovi bundle crediti e prezzi
--              Modello: Gratis(0cr), Base(1cr), Standard(3cr), Premium(10cr)
--
-- Piani: Free(30cr/0EUR), Starter(150cr/19EUR), Pro(500cr/49EUR), Agency(1500cr/99EUR)

-- Svuota e reinserisce i piani
DELETE FROM plans;

INSERT INTO plans (name, slug, credits_monthly, price_monthly, price_yearly, is_active, features) VALUES
('Free', 'free', 30, 0.00, 0.00, 1, '{"description": "Piano gratuito per provare la piattaforma", "badge_color": "slate"}'),
('Starter', 'starter', 150, 19.00, 190.00, 1, '{"description": "Ideale per freelancer e consulenti SEO", "badge_color": "blue"}'),
('Pro', 'pro', 500, 49.00, 490.00, 1, '{"description": "Per professionisti e piccoli team SEO", "badge_color": "purple", "recommended": true}'),
('Agency', 'agency', 1500, 99.00, 990.00, 1, '{"description": "Per agenzie e team con volumi elevati", "badge_color": "amber"}');

-- Aggiorna crediti free per nuovi utenti (da 50 a 30)
-- Nota: config/app.php free_credits gia aggiornato a 30
