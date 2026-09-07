#!/usr/bin/env bash
# ============================================================================
# NADI — AI Instructions Distribution Script
# ============================================================================
# Script ini mendistribusikan file .ai-instructions.md ke semua lokasi
# yang dibaca otomatis oleh berbagai AI coding assistant.
#
# Penggunaan:
#   chmod +x setup-ai-rules.sh
#   ./setup-ai-rules.sh
#
# AI tools yang didukung:
#   - Claude / Anthropic      → AGENTS.md, CLAUDE.md
#   - Google Gemini            → GEMINI.md
#   - GitHub Copilot           → .github/copilot-instructions.md
#   - Cursor                   → .cursor/rules/nadi-directives.mdc, .cursorrules
#   - Windsurf                 → .windsurfrules
#   - Cline                    → .clinerules/nadi-directives.md
#   - Aider                    → .aider.conf.yml (convention file reference)
#   - Continue.dev             → .continuerules
# ============================================================================

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
SOURCE_FILE="${SCRIPT_DIR}/.ai-instructions.md"

# Warna output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}╔══════════════════════════════════════════════════════════╗${NC}"
echo -e "${BLUE}║  NADI — AI Instructions Distribution Script             ║${NC}"
echo -e "${BLUE}╚══════════════════════════════════════════════════════════╝${NC}"
echo ""

if [ ! -f "$SOURCE_FILE" ]; then
    echo -e "❌ File sumber tidak ditemukan: ${SOURCE_FILE}"
    exit 1
fi

echo -e "${YELLOW}📄 Sumber: .ai-instructions.md${NC}"
echo ""

# Counter
count=0

# ---------------------------------------------------------------------------
# Fungsi: Copy file dengan header komentar
# ---------------------------------------------------------------------------
distribute() {
    local target="$1"
    local label="$2"
    local dir
    dir="$(dirname "$target")"

    # Buat direktori jika belum ada
    mkdir -p "$dir"

    # Copy file
    cp "$SOURCE_FILE" "$target"

    echo -e "  ${GREEN}✅${NC} ${label} → ${target##"${SCRIPT_DIR}/"}"
    count=$((count + 1))
}

# ---------------------------------------------------------------------------
# Fungsi: Buat file Cursor .mdc dengan frontmatter
# ---------------------------------------------------------------------------
distribute_cursor_mdc() {
    local target="$1"
    local dir
    dir="$(dirname "$target")"

    mkdir -p "$dir"

    # Cursor .mdc membutuhkan YAML frontmatter
    cat > "$target" << 'FRONTMATTER'
---
description: "NADI Loan Management System — Direktif Arsitektur & Aturan Mutlak untuk AI Agent"
globs: "**/*"
alwaysApply: true
---

FRONTMATTER

    # Append isi .ai-instructions.md
    cat "$SOURCE_FILE" >> "$target"

    echo -e "  ${GREEN}✅${NC} Cursor (.mdc) → ${target##"${SCRIPT_DIR}/"}"
    count=$((count + 1))
}

# ---------------------------------------------------------------------------
# 1. Claude / Anthropic → AGENTS.md
# ---------------------------------------------------------------------------
echo -e "${BLUE}[1/8] Claude / Anthropic${NC}"
distribute "${SCRIPT_DIR}/AGENTS.md" "AGENTS.md"
distribute "${SCRIPT_DIR}/CLAUDE.md" "CLAUDE.md"

# ---------------------------------------------------------------------------
# 2. Google Gemini / Antigravity → GEMINI.md
# ---------------------------------------------------------------------------
echo -e "${BLUE}[2/8] Google Gemini${NC}"
distribute "${SCRIPT_DIR}/GEMINI.md" "GEMINI.md"

# ---------------------------------------------------------------------------
# 3. GitHub Copilot → .github/copilot-instructions.md
# ---------------------------------------------------------------------------
echo -e "${BLUE}[3/8] GitHub Copilot${NC}"
distribute "${SCRIPT_DIR}/.github/copilot-instructions.md" "Copilot Instructions"

# ---------------------------------------------------------------------------
# 4. Cursor → .cursorrules + .cursor/rules/nadi-directives.mdc
# ---------------------------------------------------------------------------
echo -e "${BLUE}[4/8] Cursor${NC}"
distribute "${SCRIPT_DIR}/.cursorrules" ".cursorrules"
distribute_cursor_mdc "${SCRIPT_DIR}/.cursor/rules/nadi-directives.mdc"

# ---------------------------------------------------------------------------
# 5. Windsurf → .windsurfrules
# ---------------------------------------------------------------------------
echo -e "${BLUE}[5/8] Windsurf${NC}"
distribute "${SCRIPT_DIR}/.windsurfrules" ".windsurfrules"

# ---------------------------------------------------------------------------
# 6. Cline → .clinerules/nadi-directives.md
# ---------------------------------------------------------------------------
echo -e "${BLUE}[6/8] Cline${NC}"
distribute "${SCRIPT_DIR}/.clinerules/nadi-directives.md" "Cline Rules"

# ---------------------------------------------------------------------------
# 7. Continue.dev → .continuerules
# ---------------------------------------------------------------------------
echo -e "${BLUE}[7/8] Continue.dev${NC}"
distribute "${SCRIPT_DIR}/.continuerules" ".continuerules"

# ---------------------------------------------------------------------------
# 8. Aider → .aider.conf.yml (referensi ke file instruksi)
# ---------------------------------------------------------------------------
echo -e "${BLUE}[8/8] Aider${NC}"
cat > "${SCRIPT_DIR}/.aider.conf.yml" << 'EOF'
# NADI — Aider Configuration
# File ini otomatis di-generate oleh setup-ai-rules.sh
# Referensi: .ai-instructions.md

read:
  - .ai-instructions.md
  - MASTER_BUILD_SPECIFICATION.md
  - instructions/README.md
  - instructions/00_MASTER_INDEX_AND_CORE_DIRECTIVES.md
  - instructions/01_ARCHITECTURE_AND_STANDARDS.md
  - instructions/02_DATABASE_SCHEMA_AND_RELATIONSHIPS.md
  - instructions/03_RBAC_ROLES_AND_PERMISSIONS.md
  - instructions/04_LOAN_ENGINE_AND_FINANCIALS.md
  - instructions/05_PAYMENT_AND_REVERSAL_SYSTEM.md
  - instructions/06_COLLECTION_AND_LC_MODULE.md
  - instructions/07_COLLATERAL_AND_RELEASE_PROTOCOL.md
  - instructions/08_UI_UX_DASHBOARDS_AND_NAVIGATION.md
  - instructions/09_AUDIT_LOG_AND_REPORTING.md
  - instructions/10_SECURITY_VALIDATION_AND_PRIVACY.md
  - instructions/11_TESTING_AND_VERIFICATION_SPEC.md
  - instructions/12_BUILD_STRATEGY_AND_QUALITY_CHECKLIST.md
EOF
echo -e "  ${GREEN}✅${NC} Aider → .aider.conf.yml"
count=$((count + 1))

# ---------------------------------------------------------------------------
# Selesai
# ---------------------------------------------------------------------------
echo ""
echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}✅ Selesai! ${count} file berhasil didistribusikan.${NC}"
echo -e "${GREEN}══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${YELLOW}📋 File yang di-generate:${NC}"
echo "   ├── AGENTS.md                           (Claude/Anthropic)"
echo "   ├── CLAUDE.md                           (Claude)"
echo "   ├── GEMINI.md                           (Google Gemini)"
echo "   ├── .github/copilot-instructions.md     (GitHub Copilot)"
echo "   ├── .cursorrules                        (Cursor - legacy)"
echo "   ├── .cursor/rules/nadi-directives.mdc   (Cursor - modular)"
echo "   ├── .windsurfrules                      (Windsurf)"
echo "   ├── .clinerules/nadi-directives.md      (Cline)"
echo "   ├── .continuerules                      (Continue.dev)"
echo "   └── .aider.conf.yml                     (Aider)"
echo ""
echo -e "${YELLOW}💡 Tip:${NC} Edit hanya file ${BLUE}.ai-instructions.md${NC} lalu jalankan"
echo "   ulang script ini untuk menyinkronkan ke semua AI tools."
echo ""
