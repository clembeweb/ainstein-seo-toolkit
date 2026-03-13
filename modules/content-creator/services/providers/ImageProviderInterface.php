<?php

namespace Modules\ContentCreator\Services\Providers;

/**
 * Interface for AI image generation providers.
 *
 * Ogni provider accetta un'immagine sorgente + prompt
 * e ritorna un'immagine generata (binary data).
 *
 * Implementazioni: GeminiImageProvider (v1)
 * Future: FashnProvider (try-on), StabilityProvider (staging)
 */
interface ImageProviderInterface
{
    /**
     * Genera un'immagine a partire da un'immagine sorgente e un prompt.
     *
     * @param string $sourceImagePath Path assoluto all'immagine sorgente
     * @param string $prompt Prompt completo per la generazione
     * @return array{
     *   success: bool,
     *   image_data: ?string,
     *   mime: ?string,
     *   revised_prompt: ?string,
     *   error: ?string
     * }
     */
    public function generate(string $sourceImagePath, string $prompt): array;

    public function getProviderName(): string;
    public function getMaxImageSize(): int;
    public function getSupportedFormats(): array;
}
