#!/bin/bash
TARDIR=$1
TARNAME=$2

cd $TARDIR
tar -xvf $TARNAME
rm $TARNAME
