#!/usr/bin/env bash
set -Eeuo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
REPO_ROOT="$(cd "${SCRIPT_DIR}/.." && pwd)"

SOURCE_PHP_DIR="${SOURCE_PHP_DIR:-${REPO_ROOT}/php}"
SOURCE_NGINX_CONF="${SOURCE_NGINX_CONF:-${REPO_ROOT}/nginx/default.conf}"

LIVE_PHP_DIR="${LIVE_PHP_DIR:-/root/deploy/php}"
LIVE_NGINX_CONF="${LIVE_NGINX_CONF:-/root/deploy/nginx/default.conf}"

PHP_CONTAINER="${PHP_CONTAINER:-php-prod}"
NGINX_CONTAINER="${NGINX_CONTAINER:-nginx-prod}"

DEPLOY_PHP="${DEPLOY_PHP:-1}"
DEPLOY_NGINX="${DEPLOY_NGINX:-0}"

AI_SAFETY_REQUIRED_FILES=(
    "app/api/controller/AiService.php"
    "app/common/service/KnowledgeIndexer.php"
    "app/common/service/AiSafetyService.php"
    "app/adm/controller/AiKnowledge.php"
    "app/adm/controller/AiSafety.php"
    "app/command/AiSafetyClean.php"
    "config/console.php"
    "config/ai_safety.php"
    "view/adm/ai_knowledge/index.html"
    "view/adm/ai_safety/logs.html"
    "view/adm/ai_safety/words.html"
    "view/adm/ai_safety/word_add.html"
    "view/adm/ai_safety/word_edit.html"
    "database/patch_20260702_ai_customer_safety.sql"
)
log() {
    printf '[deploy] %s\n' "$*"
}

fail() {
    printf '[deploy] %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "缺少命令：$1"
}
require_ai_safety_files() {
    local base_dir="$1"
    local scope_name="$2"

    for file in "${AI_SAFETY_REQUIRED_FILES[@]}"; do
        [ -e "${base_dir}/${file}" ] || fail "${scope_name} 缺少 AI 客服安全文件：${file}"
    done
}

sync_php() {
    log "开始同步 PHP 项目到 ${LIVE_PHP_DIR}"
    [ -d "${SOURCE_PHP_DIR}" ] || fail "源码目录不存在：${SOURCE_PHP_DIR}"
    require_command rsync
    require_command docker
    require_ai_safety_files "${SOURCE_PHP_DIR}" "源码目录"

    mkdir -p "${LIVE_PHP_DIR}"

    rsync -a --delete \
        --exclude '.git/' \
        --exclude '.env' \
        --exclude 'runtime/' \
        --exclude 'extend/Guanjiapo.php' \
        --exclude 'public/uploads/' \
        --exclude 'public/geoflow-shopify-article.php' \
        --exclude 'config/ai.local.php' \
        --exclude 'config/ai-chat-config.php' \
        --exclude 'config/rag.local.php' \
        --exclude 'config/xhs.php' \
        --exclude 'config/xhs.local.php' \
        --exclude 'config/shopify-publisher-config.php' \
        --exclude 'vendor/' \
        --exclude 'composer.phar' \
        --exclude 'composer-setup.php' \
        "${SOURCE_PHP_DIR}/" "${LIVE_PHP_DIR}/"

    require_ai_safety_files "${LIVE_PHP_DIR}" "线上 PHP 目录"

    log "确保容器内工具完整"
    docker exec "${PHP_CONTAINER}" bash -c "apt-get update -qq && apt-get install -y -qq zip git 2>&1" || log "工具安装失败，跳过"

    log "检查并安装 bcmath 扩展"
    if ! docker exec "${PHP_CONTAINER}" php -m | grep -qi '^bcmath$'; then
        docker exec "${PHP_CONTAINER}" bash -c "docker-php-ext-install bcmath 2>&1" || fail "bcmath 扩展安装失败"
    fi

    log "安装 Composer 依赖"
    docker exec "${PHP_CONTAINER}" bash -c "cd /var/www/html && php composer.phar install --no-interaction 2>&1" || log "composer install 失败，请手动检查"

    log "重启 PHP 容器：${PHP_CONTAINER}"
    docker restart "${PHP_CONTAINER}" >/dev/null
}

sync_nginx() {
    log "开始同步 Nginx 配置到 ${LIVE_NGINX_CONF}"
    [ -f "${SOURCE_NGINX_CONF}" ] || fail "Nginx 配置不存在：${SOURCE_NGINX_CONF}"
    require_command docker

    mkdir -p "$(dirname "${LIVE_NGINX_CONF}")"

    local backup_file
    backup_file="${LIVE_NGINX_CONF}.bak.$(date +%Y%m%d%H%M%S)"
    if [ -f "${LIVE_NGINX_CONF}" ]; then
        cp "${LIVE_NGINX_CONF}" "${backup_file}"
        log "已备份当前 Nginx 配置：${backup_file}"
    fi

    install -m 644 "${SOURCE_NGINX_CONF}" "${LIVE_NGINX_CONF}"

    if ! docker exec "${NGINX_CONTAINER}" nginx -t; then
        if [ -f "${backup_file}" ]; then
            cp "${backup_file}" "${LIVE_NGINX_CONF}"
            log "Nginx 配置校验失败，已回滚到备份配置"
        fi
        fail "Nginx 配置校验失败，请先修复配置再部署"
    fi

    log "重启 Nginx 容器：${NGINX_CONTAINER}"
    docker restart "${NGINX_CONTAINER}" >/dev/null
}

parse_args() {
    if [ "$#" -eq 0 ]; then
        return
    fi

    DEPLOY_PHP=0
    DEPLOY_NGINX=0

    for arg in "$@"; do
        case "${arg}" in
            php)
                DEPLOY_PHP=1
                ;;
            nginx)
                DEPLOY_NGINX=1
                ;;
            all)
                DEPLOY_PHP=1
                DEPLOY_NGINX=1
                ;;
            *)
                fail "不支持的参数：${arg}。可用参数：php、nginx、all"
                ;;
        esac
    done
}

main() {
    parse_args "$@"

    if [ "${DEPLOY_PHP}" != "1" ] && [ "${DEPLOY_NGINX}" != "1" ]; then
        log "没有需要部署的内容，跳过"
        exit 0
    fi

    log "部署仓库根目录：${REPO_ROOT}"

    if [ "${DEPLOY_PHP}" = "1" ]; then
        sync_php
    fi

    if [ "${DEPLOY_NGINX}" = "1" ]; then
        sync_nginx
    fi

    log "部署完成"
}

main "$@"
