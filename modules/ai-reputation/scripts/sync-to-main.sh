#!/bin/bash
# Copia nel checkout principale (servito da Laragon) i file di questo branch già COMMITTATI.
# Uso: bash modules/ai-reputation/scripts/sync-to-main.sh   (dalla root del worktree)
set -euo pipefail
MAIN="C:/laragon/www/seo-toolkit"
BRANCH="claude/ai-reputation-radar-dd9004"
git -C "$MAIN" fetch -q . 2>/dev/null || true
git -C "$MAIN" restore --source="$BRANCH" --worktree -- \
  modules/ai-reputation \
  services/AiService.php \
  core/Models/GlobalProject.php \
  shared/views/components/nav-items.php
echo "copiati nel checkout principale: modules/ai-reputation, services/AiService.php"
