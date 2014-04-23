#!/usr/bin/env bash
if [ -d "garp" ]; then
	git subtree pull -P garp --squash git@code.grrr.nl:grrr/garp3 master
fi
