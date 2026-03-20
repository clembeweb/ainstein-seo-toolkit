-- Add Google Gemini API Key setting for image generation
INSERT INTO settings (key_name, value, is_secret)
VALUES ('google_gemini_api_key', '', 1)
ON DUPLICATE KEY UPDATE is_secret = 1;
