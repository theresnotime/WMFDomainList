#!/usr/bin/env bash
git pull
git submodule update --init
php generate.php