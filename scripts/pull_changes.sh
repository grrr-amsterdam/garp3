#!/usr/bin/env bash
if [ -d "garp" ]; then
	git subtree pull -P garp --squash git@github.com:grrr-amsterdam/garp3 develop
fi
