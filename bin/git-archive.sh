#!/bin/bash
WORKINGCOPY=$1
PREFIX=$2
TARPATH=$3
BRANCH=$4

cd $WORKINGCOPY
git archive --format tar --prefix $PREFIX/ -o $TARPATH $BRANCH
