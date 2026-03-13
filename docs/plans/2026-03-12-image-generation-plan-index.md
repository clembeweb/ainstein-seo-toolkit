# Content Creator — Image Generation: Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add bulk image generation (virtual try-on / virtual staging) to the content-creator module using Google Gemini as AI provider.

**Architecture:** New "Immagini" mode alongside existing "Contenuti" in content-creator projects. Separate tables (cc_images, cc_image_variants), provider interface for AI swap, SSE for batch generation/push. Extends existing connectors with ImageCapableConnectorInterface.

**Tech Stack:** PHP 8+, MySQL, Tailwind CSS, Alpine.js, Google Gemini API (image-to-image), PHP GD (format conversion)

**Design Spec:** `docs/plans/2026-03-12-content-creator-image-generation-design.md`

---

## Plan Chunks

Execute in order. Each chunk is self-contained and ends with a commit.

| # | File | Scope | Tasks |
|---|------|-------|-------|
| 1 | [chunk-1-database-models.md](./2026-03-12-image-gen-chunk-1-database-models.md) | Database migration + Models (Image, ImageVariant, Job update) | 1-4 |
| 2 | [chunk-2-provider-service.md](./2026-03-12-image-gen-chunk-2-provider-service.md) | ImageProviderInterface + GeminiImageProvider + ImageGenerationService | 5-7 |
| 3 | [chunk-3-connectors.md](./2026-03-12-image-gen-chunk-3-connectors.md) | ImageCapableConnectorInterface + 4 CMS connector extensions | 8-12 |
| 4 | [chunk-4-controllers-routes.md](./2026-03-12-image-gen-chunk-4-controllers-routes.md) | ImageController + ImageGeneratorController + routes.php | 13-16 |
| 5 | [chunk-5-views.md](./2026-03-12-image-gen-chunk-5-views.md) | All views: project-nav, index, import, preview, settings | 17-22 |
| 6 | [chunk-6-config-cron-docs.md](./2026-03-12-image-gen-chunk-6-config-cron-docs.md) | module.json, cron cleanup, docs update, PoC script | 23-26 |

---

## Pre-requisites

- Google Gemini API key configured in admin settings (`google_gemini_api_key`)
- `storage/images/sources/` and `storage/images/generated/` directories writable
- PHP GD extension enabled (standard on Laragon)

## Dependency Graph

```
Chunk 1 (DB + Models) ──→ Chunk 2 (Provider + Service)
                      ──→ Chunk 3 (Connectors)
                      ──→ Chunk 4 (Controllers + Routes) ← depends on Chunk 2 + 3
                                                        ──→ Chunk 5 (Views)
                                                        ──→ Chunk 6 (Config + Docs)
```

Chunks 2 and 3 can run in parallel after Chunk 1. Chunk 4 requires 2+3. Chunks 5 and 6 require 4.
