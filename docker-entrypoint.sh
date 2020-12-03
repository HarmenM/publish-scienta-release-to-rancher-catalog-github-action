#!/bin/sh
set -e

mkdir -p /root/.ssh
echo "$GITHUB_DEPLOY_KEY" > /root/.ssh/id_rsa
chmod 600 /root/.ssh/id_rsa
mkdir -p ~/.ssh
cp /root/.ssh/* ~/.ssh/ 2> /dev/null || true

git config --global user.email "$GITHUB_USER"
git config --global user.name "$GITHUB_EMAIL"

# Drone runs container with workspace as workdir
SOURCE_ABSPATH=$PWD/$SOURCE_RELPATH

# clone and go into
rm -rf /output
git clone -b $DEST_BRANCH $DEST_REPOSITORY /output

# Do the manipulations
php /app/bin/convert-versions.php convert -s$CHART_SUFFIX -c$COMMIT_SHA -- $SOURCE_ABSPATH /output/charts $CHART_VERSION $SCIENTA_VERSION

cd /output
git add .

HAS_CHANGES=$(git diff-index --name-only HEAD --)

if [ -n "$HAS_CHANGES" ]; then
    git commit -m "Releasing $SCIENTA_VERSION to helm repository"
    git push origin HEAD --
else
	echo "No changes, so nothing to commit"
fi

exec "$@"
