#!/usr/bin/env bash
set -euo pipefail
export RPMBUILD_TOPDIR=/tmp/rpmbuild
mkdir -p "$RPMBUILD_TOPDIR"/{BUILD,BUILDROOT,RPMS,SOURCES,SPECS,SRPMS} /tmp/source
cp -a /source/ai_assistant /tmp/source/ai_assistant
# Only package source directories used by the framework spec.
version=$(awk '$1 == "Version:" {print $2; exit}' /source/framework/issabel-framework.spec)
mkdir -p "/tmp/source/issabel-framework-$version"
cp -a /source/framework/framework /source/framework/additionals "/tmp/source/issabel-framework-$version/"
cp /source/framework/issabel-framework.spec "$RPMBUILD_TOPDIR/SPECS/"
for spec in "$RPMBUILD_TOPDIR/SPECS/issabel-framework.spec" /tmp/source/ai_assistant/packaging/*.spec; do
    sed -i -E "/^Release:/ s/$/.local${LOCAL_RPM_TAG}/" "$spec"
done
cd /tmp/source/ai_assistant/mcp
go test ./...
find "/tmp/source/issabel-framework-$version/framework/html/pbxapi" /tmp/source/ai_assistant/web -name '*.php' -print0 | xargs -0 -n1 php -l >/tmp/php-lint.log
tar -C /tmp/source -czf "$RPMBUILD_TOPDIR/SOURCES/issabel-framework-$version.tar.gz" "issabel-framework-$version"
rpmbuild --define "_topdir $RPMBUILD_TOPDIR" -ba "$RPMBUILD_TOPDIR/SPECS/issabel-framework.spec"
bash /tmp/source/ai_assistant/packaging/build-issabel-mcp-rpm.sh
bash /tmp/source/ai_assistant/packaging/build-issabel-ai-assistant-rpm.sh
cp -a "$RPMBUILD_TOPDIR/RPMS" "$RPMBUILD_TOPDIR/SRPMS" "$RPMBUILD_TOPDIR/SOURCES" "$RPMBUILD_TOPDIR/SPECS" /output/
cp /tmp/php-lint.log /output/
find /output/RPMS -name '*.rpm' -exec rpm -qp --queryformat '%{NAME} %{VERSION}-%{RELEASE} %{ARCH}\n' {} \;
