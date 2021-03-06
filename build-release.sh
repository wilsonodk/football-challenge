#!/usr/bin/env bash

echo "Cleaning up old tarballs"
BUILD_VERSION=$(cat BUILD_VERSION.txt)
rm "release-$BUILD_VERSION.tar.gz"

echo " "
sleep 1

echo "Creating new tarball"

BUILD_VERSION=$(cat VERSION.txt)
BUILD_VERSION="$BUILD_VERSION"+$(date +'%H%M%S')
RELEASE="release-$BUILD_VERSION.tar.gz"

echo $BUILD_VERSION > BUILD_VERSION.txt

tar cvfz $RELEASE --exclude="^*.tar.gz" --exclude=ENVIRONMENT.txt --exclude=build-release.sh --exclude=football-challenge.sql --exclude=junkies-out.sql --exclude=deploy-release.sh --exclude=.DS_Store --exclude=.git --exclude=.gitignore --exclude=.editorconfig .
echo "Finished..."

echo " "
sleep 1

echo "Deploy tarball and build version file"
scp $RELEASE wilson@odk.com:/home/wilson/junkies/.
scp BUILD_VERSION.txt wilson@odk.com:/home/wilson/junkies/.
echo "Deployed"
