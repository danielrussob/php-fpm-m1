#!/usr/bin/env bash

git clone $1 /var/www/$2
curl $3 > /var/www/dumps/$4
curl $5 > /var/www/$2/$6