#!/usr/bin/env bash
#
# Builds one .ezpkg archive from every package folder next to this script (every folder except "release" and
# "release-*", which hold release bundles, not packages).
#
# The build is reproducible: the same commit gives the same archive, byte for byte, on any machine with GNU tar
# 1.28 or later and gzip. Nothing about the checkout or the build time goes into an archive:
#   - entries are stored in name order (not in the order the file system lists them), and the folders are
#     visited in C locale order;
#   - every entry carries owner and group 0 and the modes 644 for files and 755 for folders and executables,
#     whoever made the checkout and whatever its umask;
#   - every entry carries one modification time: SOURCE_DATE_EPOCH when it is set, otherwise the time of the
#     last commit that changed the package folder (a package nobody changed keeps its bytes when other
#     packages change); outside a git checkout, the build time, with a warning. A git checkout that git
#     refuses to read stops the build instead;
#   - gzip writes no name and no time stamp into its header.
#
# Left out of every package: version control folders and files (.git, .gitignore, .gitattributes,
# .gitmodules, .svn, .hg), editor and operating system leftovers (.DS_Store, .idea, .vscode, backups ending
# in ~ such as *.php~, *.swp, backup copies such as *.bak and *.xml.bak.<step>) and node_modules. An installed
# package needs none of them: the package
# handlers install the files below each package folder, and these only come along from the working copies
# of the bundled extensions. tar applies each name at any depth, a folder with everything below it.
EXCLUDES=(
    --exclude='.git'
    --exclude='.gitignore'
    --exclude='.gitattributes'
    --exclude='.gitmodules'
    --exclude='.svn'
    --exclude='.hg'
    --exclude='.DS_Store'
    --exclude='.idea'
    --exclude='.vscode'
    --exclude='node_modules'
    --exclude='*~'
    --exclude='*.swp'
    --exclude='*.bak'
    --exclude='*.bak.*'
)

set -o pipefail
export LC_ALL=C

cd "$(dirname "$0")" || exit 1
echo "Starting build @ $(pwd)"

if ! tar --sort=name -cf /dev/null --files-from=/dev/null 2>/dev/null; then
    echo "GNU tar 1.28 or later is needed (tar --sort=name)" >&2
    exit 1
fi

failed=0
for folder in *; do
    case "${folder}" in release|release-*) continue ;; esac
    [ -d "${folder}" ] || continue

    if [ -n "${SOURCE_DATE_EPOCH:-}" ]; then
        mtime=${SOURCE_DATE_EPOCH}
    else
        mtime=$(git log -1 --format=%ct -- "${folder}" 2>/dev/null)
        if [ -z "${mtime}" ] && [ -e ../.git ]; then
            # A checkout git refuses to read (for one owned by another user: "dubious ownership") would
            # otherwise be stamped with the build time and never match the published package.
            echo "git cannot read the time of ${folder} in this checkout:" >&2
            git log -1 --format=%ct -- "${folder}" 2>&1 >/dev/null | sed 's/^/    /' >&2
            echo "Run as the checkout's owner, allow it (git config --global --add safe.directory <path>)" \
                 "or set SOURCE_DATE_EPOCH." >&2
            exit 1
        fi
        if [ -z "${mtime}" ]; then
            echo "Warning: ${folder} is not in a git checkout and SOURCE_DATE_EPOCH is not set; using the build time" >&2
            mtime=$(date +%s)
        fi
    fi

    echo "Packing ${folder}.ezpkg"
    if ! ( cd "${folder}" && tar -cf - \
                --format=gnu --sort=name \
                --owner=0 --group=0 --numeric-owner \
                --mode='u+rwX,go+rX,go-w' \
                --mtime="@${mtime}" \
                "${EXCLUDES[@]}" \
                * | gzip -n ) > "${folder}.ezpkg.tmp"; then
        echo "Failed to pack ${folder}" >&2
        failed=1
        continue
    fi
    mv -f "${folder}.ezpkg.tmp" "${folder}.ezpkg"
done

exit ${failed}
