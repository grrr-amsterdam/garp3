#!/usr/bin/env bash
if [ -d "garp" ]; then
	git subtree push -P garp git@code.grrr.nl:grrr/garp3 master
fi
