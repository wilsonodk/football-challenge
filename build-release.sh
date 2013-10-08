#!/usr/bin/env bash

BUILD_VERSION=$(cat VERSION.txt)
BUILD_VERSION="$BUILD_VERSION"+$(date +'%H%M%S')
RELEASE="release-$BUILD_VERSION.tar.gz"

echo $BUILD_VERSION > BUILD_VERSION.txt

echo "Create tarball"
tar cvfz $RELEASE --exclude=build-release.sh --exclude=football-challenge.sql --exclude=junkies-out.sql --exclude=deploy-release.sh --exclude=.DS_Store .
echo "Finished..."

echo " "

echo "Deploy tarball and build version file"
scp $RELEASE wilson@odk.com:/home/wilson/junkies/.
scp BUILD_VERSION.txt wilson@odk.com:/home/wilson/junkies/.
echo "Deployed"
